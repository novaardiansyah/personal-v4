<?php

namespace App\Filament\Resources\Emails\Pages;

use App\Enums\EmailStatus;
use App\Mail\EmailResource\DefaultMail;
use App\Models\Email;
use App\Models\File;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Mail;

class ActionEmail
{
  public static function send(): Action
  {
    return Action::make('send')
      ->action(function (Email $record, Action $action) {
        $data = [
          'name' => $record->name ?? explode('@', $record->email)[0],
          'subject' => $record->subject,
          'message' => $record->message,
          'attachments' => $record->files()->get()->map(function (File $file) {
            return $file->file_path;
          })->toArray(),
        ];

        Mail::to($record->email)->queue(new DefaultMail($data));

        $record->update([
          'status' => EmailStatus::Sent,
        ]);

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
