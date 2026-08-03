<?php

namespace App\Filament\Resources\Backups\Actions;

use App\Models\Backup;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

        $stream = static::getStream($cloudPath);

        if (!$stream) {
          Notification::make()
            ->danger()
            ->title('Download Failed')
            ->body("Cloud file '{$cloudPath}' could not be found on storage.")
            ->send();

          return null;
        }

        $fileName = $record->file_name ?: basename($cloudPath);
        $tempDisk = Storage::disk('local');
        $tempPath = 'temp-downloads/' . Str::uuid() . '_' . $fileName;

        $tempDisk->writeStream($tempPath, $stream);
        if (is_resource($stream)) {
          fclose($stream);
        }

        $localStream = $tempDisk->readStream($tempPath);

        return response()->streamDownload(function () use ($tempDisk, $tempPath, $localStream) {
          if ($localStream) {
            fpassthru($localStream);
            if (is_resource($localStream)) {
              fclose($localStream);
            }
          }
          $tempDisk->delete($tempPath);
        }, $fileName);
      });
  }

  private static function getStream(string $path)
  {
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
      $stream = @fopen($path, 'rb');
      return $stream ?: null;
    }

    $disks = ['r2', 's3', 'public', 'local'];

    foreach ($disks as $diskName) {
      if (!config("filesystems.disks.{$diskName}")) {
        continue;
      }

      try {
        if (Storage::disk($diskName)->exists($path)) {
          return Storage::disk($diskName)->readStream($path);
        }
      } catch (Throwable $e) {
        continue;
      }
    }

    return null;
  }
}
