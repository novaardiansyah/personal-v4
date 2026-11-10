<?php

namespace App\Filament\Resources\BackupSchedules\Actions;

use App\Models\BackupSchedule;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class DisableBulkAction
{
  public static function make(): BulkAction
  {
    return BulkAction::make('disable')
      ->label('Disable')
      ->icon(Heroicon::OutlinedXCircle)
      ->color('warning')
      ->requiresConfirmation()
      ->modalHeading('Disable Backup Schedules')
      ->modalDescription('Are you sure you want to disable the selected backup schedules?')
      ->deselectRecordsAfterCompletion()
      ->action(function (Collection $records): void {
        $disabled = 0;
        $failed   = 0;

        $records->each(function (BackupSchedule $record) use (&$disabled, &$failed): void {
          try {
            $updated = $record->update([
              'is_enabled' => false,
            ]);

            $updated ? $disabled++ : $failed++;
          } catch (Throwable) {
            $failed++;
          }
        });

        if ($failed > 0) {
          Notification::make()
            ->warning()
            ->title('Backup Schedules Partially Disabled')
            ->body("{$disabled} schedule(s) disabled successfully, {$failed} failed to update.")
            ->send();

          return;
        }

        Notification::make()
          ->success()
          ->title('Backup Schedules Disabled')
          ->body("{$disabled} schedule(s) have been disabled successfully.")
          ->send();
      });
  }
}
