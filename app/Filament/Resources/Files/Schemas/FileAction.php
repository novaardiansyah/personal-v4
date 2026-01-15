<?php

namespace App\Filament\Resources\Files\Schemas;

use App\Models\File;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
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
          ),

        Grid::make(3)
          ->schema([
            TextInput::make('file_alias')
              ->label('Display Name')
              ->maxLength(255)
              ->columnSpan(2),

            DatePicker::make('scheduled_deletion_time')
              ->label('Expiry Date')
              ->required()
              ->default(now()->addMonth())
              ->native(false)
              ->displayFormat('M d, Y')
              ->closeOnDateSelection(),
          ])
      ])
      ->action(function (array $data, Action $action) {
        $user = getUser();
        $files = $data['files'];

        $expiration = $data['scheduled_deletion_time'] ?? now()->addMonth();
        $expiration = carbonTranslatedFormat($expiration, 'Y-m-d H:i:s');

        $file_alias = $data['file_alias'] ?? null;

        foreach ($files as $file) {
          $filename = pathinfo($file, PATHINFO_BASENAME);
          $filenameWithoutExtension = pathinfo($filename, PATHINFO_FILENAME);
          $extension = pathinfo($filename, PATHINFO_EXTENSION);

          $fileUrl = URL::temporarySignedRoute(
            'download',
            $expiration,
            ['path' => $filenameWithoutExtension, 'extension' => $extension, 'directory' => 'public/attachments']
          );

          File::create([
            'file_alias' => $file_alias . '.' . $extension,
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
