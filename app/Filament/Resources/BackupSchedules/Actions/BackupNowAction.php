<?php

namespace App\Filament\Resources\BackupSchedules\Actions;

use App\Models\BackupSchedule;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class BackupNowAction
{
  public static function make(): Action
  {
    return Action::make('backup_now')
      ->label('Backup Now')
      ->icon(Heroicon::OutlinedPlay)
      ->color('success')
      ->requiresConfirmation()
      ->modalHeading('Backup Now')
      ->modalDescription('Are you sure you want to run backup now? This will set the next backup time to the current timestamp.')
      ->action(function (BackupSchedule $record): void {
        $record->update([
          'next_backup_at' => now(),
        ]);

        Notification::make()
          ->success()
          ->title('Backup Scheduled')
          ->body('The next backup time has been updated to current timestamp.')
          ->send();
      });
  }
}
