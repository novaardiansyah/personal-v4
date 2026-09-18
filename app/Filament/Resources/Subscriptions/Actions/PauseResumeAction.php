<?php

namespace App\Filament\Resources\Subscriptions\Actions;

use App\Models\Subscription;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class PauseResumeAction
{
  public static function make(): Action
  {
    return Action::make('pause_resume')
      ->label(fn(Subscription $record): string => $record->is_paused ? 'Resume' : 'Pause')
      ->icon(fn(Subscription $record) => $record->is_paused ? Heroicon::OutlinedPlayCircle : Heroicon::OutlinedPauseCircle)
      ->color(fn(Subscription $record): string => $record->is_paused ? 'primary' : 'warning')
      ->requiresConfirmation()
      ->modalHeading(fn(Subscription $record): string => $record->is_paused ? 'Resume Subscription' : 'Pause Subscription')
      ->modalDescription(fn(Subscription $record): string => $record->is_paused ? 'Are you sure you want to resume this subscription?' : 'Are you sure you want to pause this subscription?')
      ->action(function (Subscription $record): void {
        $isPaused = !$record->is_paused;
        $record->update(['is_paused' => $isPaused]);

        Notification::make()
          ->success()
          ->title($isPaused ? 'Subscription Paused' : 'Subscription Resumed')
          ->body($isPaused ? 'Subscription has been paused successfully.' : 'Subscription has been resumed successfully.')
          ->send();
      });
  }
}