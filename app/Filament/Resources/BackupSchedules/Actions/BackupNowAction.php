<?php

namespace App\Filament\Resources\BackupSchedules\Actions;

use App\Models\BackupSchedule;
use App\Models\BackupStorage;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
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

  public static function makeHeader(): Action
  {
    return Action::make('backup_now_header')
      ->label('Backup Now')
      ->color('primary')
      ->modalHeading('Backup Now')
      ->modalDescription('Select a storage, a server, or both. All matching backup schedules will have their next backup time set to the current timestamp.')
      ->modalSubmitActionLabel('Backup Now')
      ->modalIcon(Heroicon::OutlinedPlay)
      ->modalWidth(Width::Small)
      ->schema([
        Select::make('storage_id')
          ->label('Storage')
          ->options(fn() => BackupStorage::orderBy('name', 'asc')->pluck('name', 'id'))
          ->searchable()
          ->preload()
          ->native(false)
          ->nullable(),
        Select::make('server_id')
          ->label('Server')
          ->options(fn() => BackupStorage::orderBy('name', 'asc')->pluck('name', 'id'))
          ->searchable()
          ->preload()
          ->native(false)
          ->nullable(),
      ])
      ->action(function (array $data): void {
        $storageId = $data['storage_id'] ?? null;
        $serverId  = $data['server_id'] ?? null;

        if (empty($storageId) && empty($serverId)) {
          Notification::make()
            ->warning()
            ->title('Selection Required')
            ->body('Please select at least one storage or server to continue.')
            ->send();

          return;
        }

        $now   = now();
        $query = BackupSchedule::query();

        if (! empty($storageId)) {
          $query->where('storage_id', $storageId);
        }

        if (! empty($serverId)) {
          $query->where('server_id', $serverId);
        }

        $count = 0;

        $query->each(function (BackupSchedule $schedule) use ($now, &$count): void {
          $schedule->update([
            'next_backup_at' => $now,
          ]);

          $count++;
        });

        Notification::make()
          ->success()
          ->title('Backup Scheduled')
          ->body("The next backup time has been updated to the current timestamp for {$count} schedule(s).")
          ->send();
      });
  }
}

