<?php

namespace App\Filament\Resources\Files\Actions;

use App\Enums\FileType;
use App\Filament\Resources\Files\FileResource;
use App\Models\File;
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
      ->visible(fn(File $record): bool => (int) $record->type_id === FileType::DeviceFile->value)
      ->requiresConfirmation()
      ->modalHeading('Replicate File')
      ->modalDescription('Are you sure you want to replicate this file record?')
      ->action(function (File $record, Action $action): void {
        $replica             = $record->replicate();
        $replica->uid        = uuid7();
        $replica->file_alias = ($record->file_alias ?: $record->file_name) . ' (copy)';
        $replica->save();

        Notification::make()
          ->success()
          ->title('File Replicated')
          ->body('File record has been replicated successfully.')
          ->send();

        $action->redirect(FileResource::getUrl('edit', ['record' => $replica]));
      });
  }
}
