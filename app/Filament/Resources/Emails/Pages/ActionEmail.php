<?php

namespace App\Filament\Resources\Emails\Pages;

use App\Enums\EmailStatus;
use App\Mail\EmailResource\DefaultMail;
use App\Models\Email;
use App\Models\File;
use App\Services\EmailResource\EmailService;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Mail;

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
      ->label('Preview')
      ->icon('heroicon-s-eye')
      ->color('primary')
      ->url(fn(Email $record): string => route('admin.emails.preview', $record))
      ->openUrlInNewTab();
  }
}
