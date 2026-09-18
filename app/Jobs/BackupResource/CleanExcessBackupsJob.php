<?php

namespace App\Jobs\BackupResource;

use App\Enums\DiscordMessageStatus;
use App\Models\Backup;
use App\Models\BackupSchedule;
use App\Models\BackupStorage;
use App\Models\DiscordMessage;
use App\Services\DiscordWebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CleanExcessBackupsJob implements ShouldQueue
{
  use Queueable;

  private array $googleAccessTokens = [];

  public function handle(): void
  {
    $totalDeletedFiles = 0;
    $totalDeletedSize  = 0;

    BackupSchedule::chunk(5, function ($schedules) use (&$totalDeletedFiles, &$totalDeletedSize): void {
      foreach ($schedules as $schedule) {
        $maxCount = (int) ($schedule->max_count_backup ?? 5);

        if ($maxCount <= 0) {
          continue;
        }

        $totalBackups = $schedule->backups()->count();

        if ($totalBackups <= $maxCount) {
          continue;
        }

        $excessCount = $totalBackups - $maxCount;

        while ($excessCount > 0) {
          $chunkSize    = min($excessCount, 5);
          $backupsChunk = $schedule->backups()
            ->with(['storage.provider', 'backupJob.storage.provider'])
            ->orderBy('created_at', 'asc')
            ->take($chunkSize)
            ->get();

          if ($backupsChunk->isEmpty()) {
            break;
          }

          foreach ($backupsChunk as $backup) {
            $this->deleteCloudFile($backup, $schedule);
            $this->deleteLocalFile($backup->file_path);

            $totalDeletedFiles++;
            $totalDeletedSize += (int) ($backup->file_size ?? 0);

            $backupJob = $backup->backupJob;

            $backup->delete();

            if ($backupJob && $backupJob->backups()->count() === 0) {
              $backupJob->delete();
            }
          }

          $excessCount -= $backupsChunk->count();
        }
      }
    });

    $this->sendNotification($totalDeletedFiles, $totalDeletedSize);
  }

  private function sendNotification(int $totalDeletedFiles, int $totalDeletedSize): void
  {
    if ($totalDeletedFiles > 0) {
      $formattedSize = sizeFormat((float) $totalDeletedSize);

      $message = "Schedule Backup Cleanup\n\n"
        . "Total files deleted: {$totalDeletedFiles}\n"
        . "Total size deleted: {$formattedSize}";
    } else {
      $message = "Schedule Backup Cleanup\n\n"
        . "Backup cleanup check completed. No excess backup files were deleted.\n\n"
        . "Next check will run according to schedule.";
    }

    sendTelegramNotification($message);
    $this->sendDiscordNotification($totalDeletedFiles, $totalDeletedSize);
  }

  private function sendDiscordNotification(int $totalDeletedFiles, int $totalDeletedSize): void
  {
    $webhookSetting = getSetting('discord_webhook_report', null, Backup::class);
    if (empty($webhookSetting)) {
      return;
    }

    $service = app(DiscordWebhookService::class);
    $webhook = $service->resolveWebhook($webhookSetting);

    if (!$webhook) {
      return;
    }

    $payload = $service->buildBackupCleanupReportPayload($totalDeletedFiles, $totalDeletedSize);

    DiscordMessage::create([
      'webhook_id' => $webhook->id,
      'content'    => $payload,
      'status'     => DiscordMessageStatus::Pending,
    ]);
  }

  private function deleteCloudFile(Backup $backup, ?BackupSchedule $schedule = null): void
  {
    $cloudPath = $backup->cloud_file_path;

    if (empty($cloudPath)) {
      return;
    }

    $storage      = $backup->storage ?? $backup->backupJob?->storage ?? $schedule?->storage;
    $storage?->loadMissing('provider');
    $providerSlug = strtolower((string) ($storage?->provider?->slug ?? ''));

    match ($providerSlug) {
      'google-drive'  => $this->deleteGoogleDriveFile($storage, $cloudPath),
      'cloudflare-r2' => $this->deleteCloudflareR2File($cloudPath),
      default         => $this->deleteFallbackCloudFile($cloudPath),
    };
  }

  private function deleteGoogleDriveFile(?BackupStorage $storage, string $cloudPath): void
  {
    if (!$storage) {
      return;
    }

    $accessToken = $this->getGoogleDriveAccessToken($storage);

    if (empty($accessToken)) {
      return;
    }

    $fileId = $this->resolveGoogleDriveFileId($cloudPath, $accessToken);

    if (empty($fileId)) {
      Log::warning('Google Drive file ID could not be resolved for deletion', [
        'cloud_path' => $cloudPath,
      ]);
      return;
    }

    try {
      $response = Http::withHeaders([
        'Authorization' => "Bearer {$accessToken}",
      ])->delete("https://www.googleapis.com/drive/v3/files/{$fileId}", [
        'supportsAllDrives' => 'true',
      ]);

      if (!$response->successful() && $response->status() !== 404) {
        Log::error('Failed deleting file from Google Drive: ' . $response->body(), [
          'file_id'    => $fileId,
          'cloud_path' => $cloudPath,
        ]);
      }
    } catch (Throwable $e) {
      Log::error('Exception deleting file from Google Drive: ' . $e->getMessage(), [
        'file_id'    => $fileId,
        'cloud_path' => $cloudPath,
      ]);
    }
  }

  private function getGoogleDriveAccessToken(BackupStorage $storage): ?string
  {
    if (isset($this->googleAccessTokens[$storage->id])) {
      return $this->googleAccessTokens[$storage->id];
    }

    $keys         = is_array($storage->keys) ? $storage->keys : [];
    $clientId     = $keys['oauth_client_id'] ?? $keys['client_id'] ?? null;
    $clientSecret = $keys['oauth_client_secret'] ?? $keys['client_secret'] ?? null;
    $refreshToken = $keys['oauth_refresh_token'] ?? $keys['refresh_token'] ?? null;
    $tokenUri     = $keys['oauth_token_uri'] ?? 'https://oauth2.googleapis.com/token';

    if (!$refreshToken || !$clientId || !$clientSecret) {
      Log::error('Google Drive OAuth credentials are not properly configured on storage ID: ' . $storage->id);
      return null;
    }

    try {
      $tokenResponse = Http::asForm()->post($tokenUri, [
        'client_id'     => $clientId,
        'client_secret' => $clientSecret,
        'refresh_token' => $refreshToken,
        'grant_type'    => 'refresh_token',
      ]);

      if (!$tokenResponse->successful()) {
        Log::error('Failed to obtain Google Drive access token for backup deletion: ' . ($tokenResponse->json('error_description') ?? $tokenResponse->body()));
        return null;
      }

      $accessToken = $tokenResponse->json('access_token');
      if (!$accessToken) {
        Log::error('Invalid Google Drive access token received for backup deletion.');
        return null;
      }

      $this->googleAccessTokens[$storage->id] = $accessToken;

      return $accessToken;
    } catch (Throwable $e) {
      Log::error('Exception requesting Google Drive access token: ' . $e->getMessage());
      return null;
    }
  }

  private function resolveGoogleDriveFileId(string $cloudPath, string $accessToken): ?string
  {
    if (preg_match('/(?:\/file\/d\/|[?&]id=)([a-zA-Z0-9_-]+)/', $cloudPath, $matches)) {
      return $matches[1];
    }

    if (str_contains($cloudPath, '/') || str_contains($cloudPath, '.') || str_contains($cloudPath, '\\')) {
      $searchName = basename(str_replace('\\', '/', $cloudPath));
      $query      = "name = '" . addcslashes($searchName, "'\\") . "' and trashed = false";

      $searchResponse = Http::withHeaders([
        'Authorization' => "Bearer {$accessToken}",
      ])->get('https://www.googleapis.com/drive/v3/files', [
        'q'                         => $query,
        'fields'                    => 'files(id, name, size)',
        'supportsAllDrives'         => 'true',
        'includeItemsFromAllDrives' => 'true',
        'pageSize'                  => 1,
      ]);

      if ($searchResponse->successful()) {
        $files = $searchResponse->json('files');
        if (!empty($files) && isset($files[0]['id'])) {
          return $files[0]['id'];
        }
      }

      return null;
    }

    return $cloudPath;
  }

  private function deleteCloudflareR2File(string $cloudPath): void
  {
    try {
      if (Storage::disk('r2')->exists($cloudPath)) {
        Storage::disk('r2')->delete($cloudPath);
      }
    } catch (Throwable $e) {
      Log::error('Failed deleting cloud backup file on disk r2: ' . $e->getMessage(), [
        'cloud_path' => $cloudPath,
      ]);
    }
  }

  private function deleteFallbackCloudFile(string $cloudPath): void
  {
    try {
      if (config('filesystems.disks.r2.key') && Storage::disk('r2')->exists($cloudPath)) {
        Storage::disk('r2')->delete($cloudPath);
        return;
      }

      if (Storage::disk('public')->exists($cloudPath)) {
        Storage::disk('public')->delete($cloudPath);
        return;
      }

      if (Storage::disk('local')->exists($cloudPath)) {
        Storage::disk('local')->delete($cloudPath);
      }
    } catch (Throwable $e) {
      Log::error('Failed deleting fallback cloud backup file: ' . $e->getMessage(), [
        'cloud_path' => $cloudPath,
      ]);
    }
  }

  private function deleteLocalFile(?string $filePath): void
  {
    if (empty($filePath)) {
      return;
    }

    try {
      if (Storage::disk('public')->exists($filePath)) {
        Storage::disk('public')->delete($filePath);
      } elseif (file_exists($filePath) && is_file($filePath)) {
        @unlink($filePath);
      }
    } catch (Throwable $e) {
      Log::error('Failed deleting local backup file: ' . $e->getMessage(), [
        'file_path' => $filePath,
      ]);
    }
  }
}
