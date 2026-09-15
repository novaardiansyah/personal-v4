<?php

namespace App\Filament\Resources\BackupSchedules\Actions;

use App\Jobs\BackupResource\SyncAllBackupSchedulesJob;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class SyncAllBackupsAction
{
  public static function make(): Action
  {
    return Action::make('sync_all_backups')
      ->label('Sync All Backups')
      ->icon(Heroicon::OutlinedArrowPath)
      ->color('info')
      ->requiresConfirmation()
      ->modalHeading('Sync All Backups')
      ->modalDescription('Are you sure you want to synchronize all backup schedules in the background?')
      ->action(function (): void {
        SyncAllBackupSchedulesJob::dispatch(auth()->user() ?? getUser());

        Notification::make()
          ->info()
          ->title('Synchronization Dispatched')
          ->body('Backup schedules synchronization job has been queued in the background.')
          ->send();
      });
  }
}
