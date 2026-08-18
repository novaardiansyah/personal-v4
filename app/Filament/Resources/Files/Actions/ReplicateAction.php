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
        $oldUid  = $record->uid;
        $newUid  = uuid7();
        $replica = $record->replicate();

        if (!empty($oldUid)) {
          if ($replica->file_name && str_contains(strtolower($replica->file_name), strtolower($oldUid))) {
            $replica->file_name = str_ireplace($oldUid, $newUid, $replica->file_name);
          }

          if ($replica->file_path && str_contains(strtolower($replica->file_path), strtolower($oldUid))) {
            $replica->file_path = str_ireplace($oldUid, $newUid, $replica->file_path);
          }
        }

        $replica->uid        = $newUid;
        $replica->file_alias = ($record->file_alias ?: $record->file_name);
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
