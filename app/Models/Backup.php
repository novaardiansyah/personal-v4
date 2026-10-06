<?php

namespace App\Models;

use App\Enums\BackupStatus;
use App\Enums\BackupType;
use App\Observers\BackupObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

#[ObservedBy([BackupObserver::class])]
class Backup extends Model
{
  use SoftDeletes;

  protected $table = 'backups';

  protected $fillable = [
    'storage_id',
    'server_id',
    'schedule_id',
    'uid',
    'file_name',
    'file_path',
    'cloud_file_path',
    'file_size',
    'checksum',
    'type',
    'started_at',
    'completed_at',
    'duration',
    'status',
    'message',
  ];

  protected $casts = [
    'storage_id'      => 'integer',
    'server_id'       => 'integer',
    'schedule_id'     => 'integer',
    'uid'             => 'string',
    'file_name'       => 'string',
    'file_path'       => 'string',
    'cloud_file_path' => 'string',
    'file_size'       => 'integer',
    'checksum'        => 'string',
    'type'            => BackupType::class,
    'started_at'      => 'datetime',
    'completed_at'    => 'datetime',
    'duration'        => 'integer',
    'status'          => BackupStatus::class,
    'message'         => 'string',
    'deleted_at'      => 'datetime',
  ];

  public function storage(): BelongsTo
  {
    return $this->belongsTo(BackupStorage::class, 'storage_id');
  }

  public function server(): BelongsTo
  {
    return $this->belongsTo(BackupStorage::class, 'server_id');
  }

  public function schedule(): BelongsTo
  {
    return $this->belongsTo(BackupSchedule::class, 'schedule_id');
  }

  public function deleteFiles(): void
  {
    $this->deleteLocalFile();
    $this->deleteCloudFile();
  }

  public function deleteLocalFile(): void
  {
    if (empty($this->file_path)) {
      return;
    }

    try {
      if (file_exists($this->file_path)) {
        @unlink($this->file_path);
      }

      if (Storage::disk('public')->exists($this->file_path)) {
        Storage::disk('public')->delete($this->file_path);
      }

      if (Storage::disk('local')->exists($this->file_path)) {
        Storage::disk('local')->delete($this->file_path);
      }
    } catch (Throwable $e) {
      Log::error("Failed to delete local backup file: {$this->file_path}", [
        'error'     => $e->getMessage(),
        'backup_id' => $this->id,
      ]);
    }
  }

  public function deleteCloudFile(): void
  {
    if (empty($this->cloud_file_path)) {
      return;
    }

    $storage = $this->storage;
    $storage?->loadMissing('provider');
    $providerSlug = strtolower((string) $storage?->provider?->slug);

    try {
      match ($providerSlug) {
        'google-drive'  => $this->deleteGoogleDriveFile($storage, $this->cloud_file_path),
        'cloudflare-r2' => $this->deleteCloudflareR2File($this->cloud_file_path),
        default         => $this->deleteFallbackCloudFile($this->cloud_file_path),
      };
    } catch (Throwable $e) {
      Log::error("Failed to delete cloud backup file: {$this->cloud_file_path}", [
        'error'     => $e->getMessage(),
        'backup_id' => $this->id,
      ]);
    }
  }

  private function deleteGoogleDriveFile(?BackupStorage $storage, string $cloudPath): void
  {
    $keys         = is_array($storage?->keys) ? $storage->keys : [];
    $clientId     = $keys['oauth_client_id'] ?? $keys['client_id'] ?? null;
    $clientSecret = $keys['oauth_client_secret'] ?? $keys['client_secret'] ?? null;
    $refreshToken = $keys['oauth_refresh_token'] ?? $keys['refresh_token'] ?? null;
    $tokenUri     = $keys['oauth_token_uri'] ?? 'https://oauth2.googleapis.com/token';

    if (!$refreshToken || !$clientId || !$clientSecret) {
      return;
    }

    $tokenResponse = Http::asForm()->post($tokenUri, [
      'client_id'     => $clientId,
      'client_secret' => $clientSecret,
      'refresh_token' => $refreshToken,
      'grant_type'    => 'refresh_token',
    ]);

    if (!$tokenResponse->successful()) {
      return;
    }

    $accessToken = $tokenResponse->json('access_token');
    if (!$accessToken) {
      return;
    }

    $fileId = $this->resolveGoogleDriveFileId($cloudPath, $accessToken);
    if (empty($fileId)) {
      return;
    }

    Http::withHeaders([
      'Authorization' => "Bearer {$accessToken}",
    ])->delete("https://www.googleapis.com/drive/v3/files/{$fileId}?supportsAllDrives=true");
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
        'fields'                    => 'files(id, name)',
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
    if (config('filesystems.disks.r2.key')) {
      if (Storage::disk('r2')->exists($cloudPath)) {
        Storage::disk('r2')->delete($cloudPath);
      }
    }
  }

  private function deleteFallbackCloudFile(string $cloudPath): void
  {
    if (Storage::disk('public')->exists($cloudPath)) {
      Storage::disk('public')->delete($cloudPath);
    }

    if (Storage::disk('local')->exists($cloudPath)) {
      Storage::disk('local')->delete($cloudPath);
    }
  }
}
