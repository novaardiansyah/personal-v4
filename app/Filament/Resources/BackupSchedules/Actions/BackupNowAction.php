<?php

namespace App\Filament\Resources\BackupSchedules\Actions;

use App\Models\BackupSchedule;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;

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

  public static function makeBulk(): BulkAction
  {
    return BulkAction::make('backup_now_bulk')
      ->label('Backup Now')
      ->icon(Heroicon::OutlinedPlay)
      ->color('success')
      ->requiresConfirmation()
      ->modalHeading('Backup Now')
      ->modalDescription('Are you sure you want to run backup now for selected schedules? This will set the next backup time to the current timestamp.')
      ->deselectRecordsAfterCompletion()
      ->action(function (Collection $records): void {
        $now = now();

        $records->each(function (BackupSchedule $record) use ($now): void {
          $record->update([
            'next_backup_at' => $now,
          ]);
        });

        Notification::make()
          ->success()
          ->title('Backup Scheduled')
          ->body('The next backup time has been updated to current timestamp for selected schedules.')
          ->send();
      });
  }
}

