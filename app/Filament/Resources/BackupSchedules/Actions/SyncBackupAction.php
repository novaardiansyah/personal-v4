<?php

namespace App\Filament\Resources\BackupSchedules\Actions;

use App\Models\BackupSchedule;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;

class SyncBackupAction
{
  public static function make(): Action
  {
    return Action::make('sync_backup')
      ->label('Sync Backup')
      ->icon(Heroicon::OutlinedArrowPath)
      ->color('info')
      ->action(function (BackupSchedule $record): void {
        $count   = $record->backups()->count();
        $sumSize = (int) $record->backups()->sum(DB::raw('CAST(file_size AS BIGINT)'));

        $record->update([
          'count_backup'  => $count,
          'sum_file_size' => $sumSize,
        ]);

        Notification::make()
          ->success()
          ->title('Backup Synchronized')
          ->body("Backup count ({$count}) and file size (" . sizeFormat((float) $sumSize) . ") synchronized successfully.")
          ->send();
      });
  }
}
