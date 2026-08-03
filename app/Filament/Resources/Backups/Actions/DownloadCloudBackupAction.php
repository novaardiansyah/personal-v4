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

        $fileName = $record->file_name ?: basename($cloudPath);

        if (str_starts_with($cloudPath, 'http://') || str_starts_with($cloudPath, 'https://')) {
          try {
            $readStream = @fopen($cloudPath, 'rb');

            if ($readStream) {
              $tempPath = 'temp-downloads/' . Str::uuid() . '_' . $fileName;
              Storage::disk('local')->writeStream($tempPath, $readStream);

              if (is_resource($readStream)) {
                fclose($readStream);
              }

              $fullPath = Storage::disk('local')->path($tempPath);

              return response()->download($fullPath, $fileName)->deleteFileAfterSend(true);
            }
          } catch (Throwable $e) {
          }

          return redirect()->away($cloudPath);
        }

        if (class_exists('League\Flysystem\AwsS3V3\AwsS3V3Adapter') || class_exists('League\Flysystem\AwsS3V3\PortableVisibilityConverter')) {
          if (config('filesystems.disks.r2') && config('filesystems.disks.r2.key')) {
            try {
              if (Storage::disk('r2')->exists($cloudPath)) {
                $readStream = Storage::disk('r2')->readStream($cloudPath);

                if ($readStream) {
                  $tempPath = 'temp-downloads/' . Str::uuid() . '_' . $fileName;
                  Storage::disk('local')->writeStream($tempPath, $readStream);

                  if (is_resource($readStream)) {
                    fclose($readStream);
                  }

                  $fullPath = Storage::disk('local')->path($tempPath);

                  return response()->download($fullPath, $fileName)->deleteFileAfterSend(true);
                }
              }
            } catch (Throwable $e) {
            }
          }

          if (config('filesystems.disks.s3') && config('filesystems.disks.s3.key')) {
            try {
              if (Storage::disk('s3')->exists($cloudPath)) {
                $readStream = Storage::disk('s3')->readStream($cloudPath);

                if ($readStream) {
                  $tempPath = 'temp-downloads/' . Str::uuid() . '_' . $fileName;
                  Storage::disk('local')->writeStream($tempPath, $readStream);

                  if (is_resource($readStream)) {
                    fclose($readStream);
                  }

                  $fullPath = Storage::disk('local')->path($tempPath);

                  return response()->download($fullPath, $fileName)->deleteFileAfterSend(true);
                }
              }
            } catch (Throwable $e) {
            }
          }
        }

        try {
          if (Storage::disk('public')->exists($cloudPath)) {
            $readStream = Storage::disk('public')->readStream($cloudPath);

            if ($readStream) {
              $tempPath = 'temp-downloads/' . Str::uuid() . '_' . $fileName;
              Storage::disk('local')->writeStream($tempPath, $readStream);

              if (is_resource($readStream)) {
                fclose($readStream);
              }

              $fullPath = Storage::disk('local')->path($tempPath);

              return response()->download($fullPath, $fileName)->deleteFileAfterSend(true);
            }
          }

          if (Storage::disk('local')->exists($cloudPath)) {
            $fullPath = Storage::disk('local')->path($cloudPath);
            return response()->download($fullPath, $fileName);
          }
        } catch (Throwable $e) {
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
