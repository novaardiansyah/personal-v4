<?php

namespace App\Filament\Resources\FileDownloads\Pages;

use App\Models\File;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ActionFileDownload
{
  public static function upload_file(): Action
  {
    return Action::make('upload_file')
      ->label('Upload File')
      ->modalWidth(Width::TwoExtraLarge)
      ->modalHeading('Upload File')
      ->modalDescription('Upload a file to this file download')
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
        $user = getUser();
        $files = $data['files'];

        foreach ($files as $file) {
          $filename = pathinfo($file, PATHINFO_BASENAME);
          $filenameWithoutExtension = pathinfo($filename, PATHINFO_FILENAME);
          $extension = pathinfo($filename, PATHINFO_EXTENSION);

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
            'file_download_id'        => $ownerRecord->id,
          ]);
        }

        $action->successNotification(function (Notification $notification) {
          $notification
            ->body('Files have been uploaded.');
        });
      });
  }
}