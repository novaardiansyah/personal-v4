<?php

namespace App\Filament\Resources\Files\Schemas;

use App\Models\File;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class FileAction
{
  public static function create()
  {
    return CreateAction::make()
      ->label('Upload files')
      ->color('primary')
      ->modalHeading('Upload files')
      ->modalDescription('You can upload multiple files at once.')
      ->modalWidth(Width::TwoExtraLarge)
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
      ->action(function (array $data, Action $action) {
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
            'user_id' => $user->id,
            'file_name' => $filename,
            'file_path' => $file,
            'download_url' => $fileUrl,
            'scheduled_deletion_time' => $expiration,
          ]);
        }

        $action->successNotification(function (Notification $notification) {
          $notification
            ->body('Files have been uploaded.');
        });
      });
  }
}
