<?php

namespace App\Http\Controllers;

use App\Models\Backup;
use Illuminate\Http\RedirectResponse;
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

    if (str_starts_with($cloudPath, 'http://') || str_starts_with($cloudPath, 'https://')) {
      return redirect()->away($cloudPath);
    }

    $fileName = $backup->file_name ?: basename($cloudPath);

    if (class_exists('League\Flysystem\AwsS3V3\AwsS3V3Adapter') || class_exists('League\Flysystem\AwsS3V3\PortableVisibilityConverter')) {
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

      if (config('filesystems.disks.s3.key')) {
        try {
          if (Storage::disk('s3')->exists($cloudPath)) {
            try {
              $url = Storage::disk('s3')->temporaryUrl($cloudPath, now()->addMinutes(30));
              return redirect()->away($url);
            } catch (Throwable $e) {
              return Storage::disk('s3')->download($cloudPath, $fileName);
            }
          }
        } catch (Throwable $e) {
        }
      }
    }

    if (config('filesystems.disks.r2.url')) {
      $r2BaseUrl = rtrim(config('filesystems.disks.r2.url'), '/');
      $cloudUrl  = $r2BaseUrl . '/' . ltrim($cloudPath, '/');
      return redirect()->away($cloudUrl);
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
