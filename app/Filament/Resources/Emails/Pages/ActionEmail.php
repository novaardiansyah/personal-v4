<?php

namespace App\Filament\Resources\Emails\Pages;

use App\Enums\EmailStatus;
use App\Models\Email;
use App\Models\File;
use App\Services\EmailResource\EmailService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

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

  public static function uploadAttachment(): CreateAction
  {
    return CreateAction::make()
      ->label('Upload files')
      ->color('primary')
      ->modalHeading('Upload attachment')
      ->modalDescription('You can upload multiple files at once.')
      ->modalWidth(Width::Medium)
      ->schema([
        FileUpload::make('files')
          ->required()
          ->multiple()
          ->moveFiles()
          ->maxFiles(10)
          ->maxSize(1024 * 20)
          ->disk('public')
          ->directory('attachments')
          ->imageEditor()
          ->getUploadedFileNameForStorageUsing(
            fn(TemporaryUploadedFile $file): string => Str::orderedUuid()->toString() . '.' . $file->getClientOriginalExtension()
          )
      ])
      ->action(function (array $data, Action $action, RelationManager $livewire) {
        $ownerRecord = $livewire->getOwnerRecord();
        $user        = getUser();
        $files       = $data['files'];

        foreach ($files as $file) {
          $filename                 = pathinfo($file, PATHINFO_BASENAME);
          $filenameWithoutExtension = pathinfo($filename, PATHINFO_FILENAME);
          $extension                = pathinfo($filename, PATHINFO_EXTENSION);

          $expiration = now()->addMonth();

          $fileUrl = URL::temporarySignedRoute(
            'download',
            $expiration,
            ['path' => $filenameWithoutExtension, 'extension' => $extension, 'directory' => 'public/attachments']
          );

          File::create([
            'user_id'                 => $user->id,
            'file_name'               => $filename,
            'file_path'               => $file,
            'download_url'            => $fileUrl,
            'scheduled_deletion_time' => $expiration,
            'subject_id'              => $ownerRecord->id,
            'subject_type'            => Email::class,
          ]);
        }

        $action->successNotification(function (Notification $notification) {
          $notification
            ->body('Files have been uploaded.');
        });
      });
  }
}
