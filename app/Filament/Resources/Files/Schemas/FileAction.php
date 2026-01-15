<?php

namespace App\Filament\Resources\Files\Schemas;

use App\Models\File;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class FileAction
{
  public static function schema(): array
  {
    return [
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
    ];
  }

  public static function processUpload(array $data, ?array $relationData = null): void
  {
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

      $fileData = [
        'file_alias' => $file_alias ? $file_alias . '.' . $extension : null,
        'user_id' => $user->id,
        'file_name' => $filename,
        'file_path' => $file,
        'download_url' => $fileUrl,
        'scheduled_deletion_time' => $expiration,
      ];

      if ($relationData) {
        $fileData = array_merge($fileData, $relationData);
      }

      File::create($fileData);
    }
  }

  public static function create(): CreateAction
  {
    return CreateAction::make()
      ->label('Upload files')
      ->color('primary')
      ->modalHeading('Upload files')
      ->modalDescription('You can upload multiple files at once.')
      ->modalWidth(Width::TwoExtraLarge)
      ->schema(self::schema())
      ->action(function (array $data, Action $action) {
        self::processUpload($data);

        $action->successNotification(function (Notification $notification) {
          $notification->body('Files have been uploaded.');
        });
      });
  }

  public static function uploadForHasMany(string $foreignKey): Action
  {
    return Action::make('upload_file')
      ->label('Upload File')
      ->modalWidth(Width::TwoExtraLarge)
      ->modalHeading('Upload File')
      ->modalDescription('Upload a file to this record')
      ->schema(self::schema())
      ->action(function (array $data, Action $action, RelationManager $livewire) use ($foreignKey) {
        $ownerRecord = $livewire->getOwnerRecord();

        self::processUpload($data, [
          $foreignKey => $ownerRecord->id,
        ]);

        $action->successNotification(function (Notification $notification) {
          $notification->body('Files have been uploaded.');
        });
      });
  }

  public static function uploadForMorph(string $morphName = 'subject'): CreateAction
  {
    return CreateAction::make()
      ->label('Upload files')
      ->color('primary')
      ->modalHeading('Upload attachment')
      ->modalDescription('You can upload multiple files at once.')
      ->modalWidth(Width::TwoExtraLarge)
      ->schema(self::schema())
      ->action(function (array $data, Action $action, RelationManager $livewire) use ($morphName) {
        $ownerRecord = $livewire->getOwnerRecord();

        self::processUpload($data, [
          $morphName . '_id' => $ownerRecord->id,
          $morphName . '_type' => get_class($ownerRecord),
        ]);

        $action->successNotification(function (Notification $notification) {
          $notification->body('Files have been uploaded.');
        });
      });
  }
}

