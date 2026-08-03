<?php

namespace App\Filament\Resources\Backups\Actions;

use App\Models\Backup;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DownloadCloudBackupAction
{
  public static function make(): Action
  {
    return Action::make('download_cloud')
      ->label('Download')
      ->icon(Heroicon::OutlinedCloudArrowDown)
      ->color('info')
      ->visible(fn(?Backup $record): bool => filled($record?->cloud_file_path))
      ->action(function (Backup $record) {
        $cloudPath = $record->cloud_file_path;

        if (empty($cloudPath)) {
          Notification::make()
            ->warning()
            ->title('Cloud File Not Found')
            ->body('No cloud file path is set for this backup.')
            ->send();

          return null;
        }

        if (str_starts_with($cloudPath, 'http://') || str_starts_with($cloudPath, 'https://')) {
          return redirect()->away($cloudPath);
        }

        $fileName = $record->file_name ?: basename($cloudPath);

        if (class_exists('League\Flysystem\AwsS3V3\AwsS3V3Adapter') || class_exists('League\Flysystem\AwsS3V3\PortableVisibilityConverter')) {
          if (config('filesystems.disks.r2') && config('filesystems.disks.r2.key')) {
            try {
              if (Storage::disk('r2')->exists($cloudPath)) {
                return Storage::disk('r2')->download($cloudPath, $fileName);
              }
            } catch (Throwable $e) {
            }
          }

          if (config('filesystems.disks.s3') && config('filesystems.disks.s3.key')) {
            try {
              if (Storage::disk('s3')->exists($cloudPath)) {
                return Storage::disk('s3')->download($cloudPath, $fileName);
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

        Notification::make()
          ->danger()
          ->title('Download Failed')
          ->body("Cloud file '{$cloudPath}' could not be found on storage.")
          ->send();

        return null;
      });
  }
}
