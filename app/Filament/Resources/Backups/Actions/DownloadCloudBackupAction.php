<?php

namespace App\Filament\Resources\Backups\Actions;

use App\Models\Backup;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class DownloadCloudBackupAction
{
  public static function make(): Action
  {
    return Action::make('download_cloud')
      ->label('Download')
      ->icon(Heroicon::OutlinedCloudArrowDown)
      ->color('info')
      ->visible(fn(?Backup $record): bool => filled($record?->cloud_file_path))
      ->url(fn(Backup $record): string => route('admin.backups.download-cloud', $record))
      ->openUrlInNewTab();
  }
}
