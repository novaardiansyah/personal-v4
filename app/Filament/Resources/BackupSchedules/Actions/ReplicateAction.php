<?php

namespace App\Filament\Resources\BackupSchedules\Actions;

use App\Models\BackupSchedule;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class ReplicateAction
{
  public static function make(): Action
  {
    return Action::make('replicate')
      ->label('Replicate')
      ->icon(Heroicon::OutlinedDocumentDuplicate)
      ->color('warning')
      ->requiresConfirmation()
      ->modalHeading('Replicate Backup Schedule')
      ->modalDescription('Are you sure you want to replicate this backup schedule?')
      ->action(function (BackupSchedule $record): void {
        $replica             = $record->replicate();
        $replica->name       = "{$record->name} (copy)";
        $replica->is_enabled = false;
        $replica->uid        = null;
        $replica->save();

        Notification::make()
          ->success()
          ->title('Backup Schedule Replicated')
          ->body('Backup schedule has been replicated successfully.')
          ->send();
      });
  }
}
