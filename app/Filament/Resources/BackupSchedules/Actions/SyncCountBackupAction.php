<?php

namespace App\Filament\Resources\BackupSchedules\Actions;

use App\Models\BackupSchedule;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class SyncCountBackupAction
{
  public static function make(): Action
  {
    return Action::make('sync_count_backup')
      ->label('Sync Count Backup')
      ->icon(Heroicon::OutlinedArrowPath)
      ->color('info')
      ->action(function (BackupSchedule $record): void {
        $count = $record->backups()->count();
        $record->update(['count_backup' => $count]);

        Notification::make()
          ->success()
          ->title('Backup Count Synchronized')
          ->body("Backup count has been updated to {$count}.")
          ->send();
      });
  }
}
