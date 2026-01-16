<?php

namespace App\Filament\Resources\Payments\RelationManagers;

use App\Enums\GallerySize;
use App\Filament\Resources\Galleries\GalleryResource;
use App\Filament\Resources\Galleries\Pages\ManageGalleries;
use App\Jobs\GalleryResource\DeleteGalleryJob;
use App\Jobs\GalleryResource\ForceDeleteGalleryJob;
use App\Jobs\GalleryResource\RestoreGalleryJob;
use App\Jobs\GalleryResource\UploadGalleryJob;
use App\Models\Gallery;
use App\Services\GalleryResource\CdnService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class GalleriesRelationManager extends RelationManager
{
  protected static string $relationship = 'galleries';

  protected static ?string $relatedResource = GalleryResource::class;

  protected static int $numBulkQueue = 5;

  protected static ?string $title = 'Attachments';

  public function isReadOnly(): bool
  {
    return false;
  }

  public function table(Table $table): Table
  {
    return GalleryResource::table($table)
      ->defaultSort('updated_at', 'desc')
      ->headerActions([
        CreateAction::make()
          ->label('Upload Image')
          ->modalHeading('Upload image to CDN')
          ->modalWidth(Width::ThreeExtraLarge)
          ->form(fn(Schema $form): Schema => $form->components([
            FileUpload::make('file_path')
              ->label('Image')
              ->image()
              ->disk('public')
              ->directory('images/gallery')
              ->required()
              ->maxSize(10240)
              ->maxFiles(15)
              ->imageEditor()
              ->columnSpanFull()
              ->multiple(),
            Textarea::make('description')
              ->default(null)
              ->rows(3)
              ->columnSpanFull(),
            Grid::make(4)
              ->schema([
                Toggle::make('is_private')
                  ->default(false),
              ]),
          ]))
          ->action(function (CreateAction $action, array $data, RelationManager $livewire) {
            $filePath = $data['file_path'];
            $ownerRecord = $livewire->getOwnerRecord();
            $isQueued = count($filePath) > 3;

            foreach ($filePath as $path) {
              if ($isQueued) {
                UploadGalleryJob::dispatch(
                  $path,
                  $data['description'] ?? null,
                  (bool) ($data['is_private'] ?? false),
                  get_class($ownerRecord),
                  $ownerRecord->id,
                  'payment',
                );
              } else {
                app(CdnService::class)->upload(
                  $path,
                  $data['description'] ?? null,
                  (bool) ($data['is_private'] ?? false),
                  get_class($ownerRecord),
                  $ownerRecord->id,
                  'payment',
                );

                Storage::disk('public')->delete($path);
              }
            }

            if ($isQueued) {
              $action->successNotificationTitle('Background Process');
              $action->successNotification(function (Notification $notification) {
                $notification->body('You will see the result in the next page refresh');
              });
            } else {
              $action->successNotificationTitle('Images uploaded successfully');
            }
          }),
      ]);
  }
}
