<?php

namespace App\Filament\Resources\Emails\Pages;

use App\Enums\EmailStatus;
use App\Models\Email;
use App\Services\EmailResource\EmailService;
use Filament\Actions\Action;
use Filament\Actions\ReplicateAction;

class ActionEmail
{
  public static function send(): Action
  {
    return Action::make('send')
      ->action(function (Email $record, Action $action) {
        (new EmailService())->sendOrPreview($record);

        $action->success();
        $action->successNotificationTitle('Email will be sent in the background');
      })
      ->label('Send')
      ->icon('heroicon-s-paper-airplane')
      ->color('success')
      ->requiresConfirmation()
      ->modalHeading('Send Email')
      ->modalDescription('Are you sure you want to send this email?')
      ->visible(fn(Email $record): bool => $record->status === EmailStatus::Draft);
  }

  public static function preview(): Action
  {
    return Action::make('preview')
      ->label('Preview email')
      ->icon('heroicon-o-envelope')
      ->color('primary')
      ->url(fn(Email $record): string => route('admin.emails.preview', $record))
      ->openUrlInNewTab();
  }

  public static function replicate(): Action 
  {
    return ReplicateAction::make('replicate')
      ->label('Replicate')
      ->icon('heroicon-o-document-duplicate')
      ->color('warning')
      ->action(function (Email $record, Action $action) {
        $newRecord = $record->replicate(['files_count']);
        $newRecord->status = EmailStatus::Draft;
        $newRecord->subject = $record->subject . ' (Copy)';

        $newRecord->save();

        $action->success();
        $action->successNotificationTitle('Email replicated successfully');
      })
      ->requiresConfirmation()
      ->modalHeading('Replicate Email')
      ->modalDescription('Are you sure you want to replicate this email?');
  }
}
