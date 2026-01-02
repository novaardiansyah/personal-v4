<?php

namespace App\Filament\Resources\Emails\RelationManagers;

use App\Filament\Resources\Files\FileResource;
use App\Models\Email;
use App\Models\File;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DetachAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\URL;

class FilesRelationManager extends RelationManager
{
  protected static string $relationship = 'files';

  protected static ?string $relatedResource = FileResource::class;

  protected static ?string $title = 'Attachments';

  public function isReadOnly(): bool
  {
    return false;
  }

  public function table(Table $table): Table
  {
    return $table
      ->modifyQueryUsing(fn($query) => $query->withoutGlobalScopes([SoftDeletingScope::class]))
      ->recordTitleAttribute('file_name')
      ->columns([
        TextColumn::make('index')
          ->rowIndex()
          ->label('#'),
        TextColumn::make('file_name')
          ->label('File')
          ->tooltip(fn(File $record): string => $record->has_been_deleted ? 'File already removed' : 'Download File')
          ->url(fn(File $record): string|null => !$record->has_been_deleted ? $record->download_url : null, fn(File $record): bool => !$record->has_been_deleted)
          ->searchable()
          ->toggleable(),
        IconColumn::make('has_been_deleted')
          ->boolean()
          ->toggleable(),
        TextColumn::make('scheduled_deletion_time')
          ->dateTime()
          ->sortable()
          ->toggleable(),
        TextColumn::make('deleted_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('created_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('updated_at')
          ->dateTime()
          ->sortable()
          ->sinceTooltip()
          ->toggleable(isToggledHiddenByDefault: false),
      ])
      ->filters([
        TrashedFilter::make()
          ->native(false),
      ])
      ->defaultSort('updated_at', 'desc')
      ->headerActions([
        CreateAction::make()
          ->label('Upload file')
          ->modalHeading('Upload New File')
          ->modalWidth(Width::TwoExtraLarge)
          ->schema([
            FileUpload::make('files')
              ->required()
              ->multiple()
              ->maxFiles(10)
              ->maxSize(1024 * 5)
              ->disk('public')
              ->directory('attachments'),
          ])
          ->action(function (array $data, CreateAction $action, RelationManager $livewire) {
            $ownerRecord = $livewire->getOwnerRecord();
            $user        = getUser();
            $files       = $data['files'];

            foreach ($files as $file) {
              $filename = pathinfo($file, PATHINFO_BASENAME);
              $filenameWithoutExtension = pathinfo($filename, PATHINFO_FILENAME);
              $extension = pathinfo($filename, PATHINFO_EXTENSION);

              $expiration = now()->addDay();
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
                'subject_type'            => Email::class,
                'subject_id'              => $ownerRecord->id,
              ]);
            }

            $action->successNotification(function (Notification $notification) {
              $notification
                ->body('Files have been uploaded.');
            });
          }),
      ])
      ->recordActions([
        ActionGroup::make([
          ViewAction::make()
            ->modalHeading('View File Details')
            ->modalWidth(Width::FourExtraLarge)
            ->slideOver()
            ->infolist(fn(Schema $infolist) => FileResource::infolist($infolist)),

          DeleteAction::make()
        ]),
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          //
        ]),
      ]);
  }
}
