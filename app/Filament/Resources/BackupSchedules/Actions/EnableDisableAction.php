<?php

namespace App\Filament\Resources\BackupSchedules\Actions;

use App\Models\BackupSchedule;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class EnableDisableAction
{
  public static function make(): Action
  {
    return Action::make('enable_disable')
      ->label(fn(BackupSchedule $record): string => $record->is_enabled ? 'Disable' : 'Enable')
      ->icon(fn(BackupSchedule $record) => $record->is_enabled ? Heroicon::OutlinedXCircle : Heroicon::OutlinedCheckCircle)
      ->color(fn(BackupSchedule $record): string => $record->is_enabled ? 'warning' : 'success')
      ->requiresConfirmation()
      ->modalHeading(fn(BackupSchedule $record): string => $record->is_enabled ? 'Disable Backup Schedule' : 'Enable Backup Schedule')
      ->modalDescription(fn(BackupSchedule $record): string => $record->is_enabled ? 'Are you sure you want to disable this backup schedule?' : 'Are you sure you want to enable this backup schedule?')
      ->action(function (BackupSchedule $record): void {
        $isEnabled = !$record->is_enabled;
        $record->update(['is_enabled' => $isEnabled]);

        Notification::make()
          ->success()
          ->title($isEnabled ? 'Backup Schedule Enabled' : 'Backup Schedule Disabled')
          ->body($isEnabled ? 'Backup schedule has been enabled successfully.' : 'Backup schedule has been disabled successfully.')
          ->send();
      });
  }
}
