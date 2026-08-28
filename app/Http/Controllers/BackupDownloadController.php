<?php

namespace App\Http\Controllers;

use App\Models\Backup;
use App\Models\BackupStorage;
use GuzzleHttp\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class BackupDownloadController extends Controller
{
  public function downloadCloud(Backup $backup): RedirectResponse|StreamedResponse
  {
    $cloudPath = $backup->cloud_file_path;

    if (empty($cloudPath)) {
      abort(404, 'Cloud file path is empty.');
    }

    $fileName = $backup->file_name ?: "{$backup->uid}.zip";

    $storage = $backup->storage ?? $backup->backupJob?->storage;
    $storage?->loadMissing('provider');
    $providerSlug = strtolower((string) $storage?->provider?->slug);

    return match ($providerSlug) {
      'google-drive'  => $this->downloadGoogleDrive($storage, $cloudPath, $fileName),
      'cloudflare-r2' => $this->downloadCloudflareR2($storage, $cloudPath, $fileName),
      default         => $this->downloadFallback($cloudPath, $fileName),
    };
  }

  private function downloadGoogleDrive(?BackupStorage $storage, string $cloudPath, string $fileName): StreamedResponse|RedirectResponse
  {
    $keys         = is_array($storage?->keys) ? $storage->keys : [];
    $clientId     = $keys['oauth_client_id'] ?? $keys['client_id'] ?? null;
    $clientSecret = $keys['oauth_client_secret'] ?? $keys['client_secret'] ?? null;
    $refreshToken = $keys['oauth_refresh_token'] ?? $keys['refresh_token'] ?? null;
    $tokenUri     = $keys['oauth_token_uri'] ?? 'https://oauth2.googleapis.com/token';

    if (!$refreshToken || !$clientId || !$clientSecret) {
      if (str_starts_with($cloudPath, 'http://') || str_starts_with($cloudPath, 'https://')) {
        return redirect()->away($cloudPath);
      }

      abort(422, 'Google Drive OAuth credentials are not properly configured on storage.');
    }

    $tokenResponse = Http::asForm()->post($tokenUri, [
      'client_id'     => $clientId,
      'client_secret' => $clientSecret,
      'refresh_token' => $refreshToken,
      'grant_type'    => 'refresh_token',
    ]);

    if (!$tokenResponse->successful()) {
      abort(502, 'Failed to obtain Google Drive access token: ' . ($tokenResponse->json('error_description') ?? $tokenResponse->body()));
    }

    $accessToken = $tokenResponse->json('access_token');
    if (!$accessToken) {
      abort(502, 'Invalid Google Drive access token received.');
    }

    $fileId = $this->resolveGoogleDriveFileId($cloudPath, $accessToken);

    if (empty($fileId)) {
      abort(404, "File '{$cloudPath}' not found in Google Drive.");
    }

    $client = new Client();

    try {
      $guzzleResponse = $client->request('GET', "https://www.googleapis.com/drive/v3/files/{$fileId}?alt=media&supportsAllDrives=true", [
        'headers' => [
          'Authorization' => "Bearer {$accessToken}",
        ],
        'stream'  => true,
      ]);
    } catch (Throwable $e) {
      abort(502, 'Failed to download file from Google Drive: ' . $e->getMessage());
    }

    return response()->streamDownload(function () use ($guzzleResponse) {
      $body = $guzzleResponse->getBody();
      while (!$body->eof()) {
        echo $body->read(1024 * 64);
        if (ob_get_level() > 0) {
          ob_flush();
        }
        flush();
      }
    }, $fileName, [
      'Content-Type' => 'application/zip',
    ]);
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

  private function downloadCloudflareR2(?BackupStorage $storage, string $cloudPath, string $fileName): RedirectResponse|StreamedResponse
  {
    if (str_starts_with($cloudPath, 'http://') || str_starts_with($cloudPath, 'https://')) {
      return redirect()->away($cloudPath);
    }

    if (config('filesystems.disks.r2.key')) {
      try {
        if (Storage::disk('r2')->exists($cloudPath)) {
          try {
            $url = Storage::disk('r2')->temporaryUrl($cloudPath, now()->addMinutes(30));
            return redirect()->away($url);
          } catch (Throwable $e) {
            return Storage::disk('r2')->download($cloudPath, $fileName);
          }
        }
      } catch (Throwable $e) {
      }
    }

    if (config('filesystems.disks.r2.url')) {
      $r2BaseUrl = rtrim(config('filesystems.disks.r2.url'), '/');
      $cloudUrl  = $r2BaseUrl . '/' . ltrim($cloudPath, '/');
      return redirect()->away($cloudUrl);
    }

    abort(404, 'File not found on Cloudflare R2 storage.');
  }

  private function downloadFallback(string $cloudPath, string $fileName): RedirectResponse|StreamedResponse
  {
    if (str_starts_with($cloudPath, 'http://') || str_starts_with($cloudPath, 'https://')) {
      return redirect()->away($cloudPath);
    }

    try {
      if (Storage::disk('public')->exists($cloudPath)) {
        return Storage::disk('public')->download($cloudPath, $fileName);
      }

      if (Storage::disk('local')->exists($cloudPath)) {
        return Storage::disk('local')->download($cloudPath, $fileName);
      }
    } catch (Throwable $e) {
    }

    if (str_starts_with($cloudPath, '/')) {
      return redirect()->away(url($cloudPath));
    }

    abort(404, 'Cloud file not found on storage.');
  }
}
