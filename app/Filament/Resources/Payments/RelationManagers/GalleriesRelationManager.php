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
    return $table
      ->modifyQueryUsing(fn($query) => $query->withoutGlobalScopes([SoftDeletingScope::class]))
      ->recordTitleAttribute('file_name')
      ->columns([
        TextColumn::make('index')
          ->rowIndex()
          ->label('#'),
        ImageColumn::make('file_path')
          ->label('Preview')
          ->circular()
          ->state(fn($record): string => config('services.self.cdn_url') . '/' . $record->file_path),
        TextColumn::make('file_name')
          ->searchable()
          ->wrap()
          ->limit(50)
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('size')
          ->label('Size')
          ->toggleable(isToggledHiddenByDefault: false)
          ->badge()
          ->color(fn(Gallery $record) => $record->size->color()),
        TextColumn::make('file_size')
          ->formatStateUsing(fn($state) => sizeFormat($state))
          ->sortable(),
        TextColumn::make('description')
          ->searchable()
          ->wrap()
          ->limit(100)
          ->toggleable(isToggledHiddenByDefault: true),
        IconColumn::make('is_private')
          ->boolean(),
        IconColumn::make('has_optimized')
          ->boolean(),
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
        SelectFilter::make('size')
          ->options(GallerySize::class)
          ->default(GallerySize::Original->value)
          ->native(false),
        TrashedFilter::make()
          ->native(false),
      ])
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
      ])
      ->recordActions([
        ActionGroup::make([
          ViewAction::make()
            ->modalHeading('View Gallery')
            ->modalWidth(Width::FourExtraLarge)
            ->slideOver()
            ->infolist(fn(Schema $infolist) => GalleryResource::infolist($infolist)),

          DeleteAction::make()
            ->action(function (Gallery $record, Action $action) {
              $response = app(CdnService::class)->delete($record->id);

              if ($response->successful()) {
                $action->success();
                $action->successNotificationTitle('Image deleted successfully');
              } else {
                $action->failure();
                $action->failureNotificationTitle('Failed to delete image');
              }
            }),

          ForceDeleteAction::make()
            ->action(function (Gallery $record, Action $action) {
              $response = app(CdnService::class)->forceDelete($record->id);

              if ($response->successful()) {
                $action->success();
                $action->successNotificationTitle('Image deleted successfully');
              } else {
                $action->failure();
                $action->failureNotificationTitle('Failed to delete image');
              }
            }),

          RestoreAction::make()
            ->action(function (Gallery $record, Action $action) {
              $response = app(CdnService::class)->restore($record->id);

              if ($response->successful()) {
                $action->success();
                $action->successNotificationTitle('Image restored successfully');
              } else {
                $action->failure();
                $action->failureNotificationTitle('Failed to restore image');
              }
            }),
        ]),
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          DeleteBulkAction::make()
            ->action(function (Collection $records, Action $action) {
              $isQueued = $records->count() >= self::$numBulkQueue;
              $cdnService = $isQueued ? null : app(CdnService::class);

              foreach ($records as $record) {
                $isQueued
                  ? DeleteGalleryJob::dispatch($record->id)
                  : $cdnService->delete($record->id);
              }

              if ($isQueued) {
                ManageGalleries::_backgroundNotification();
                return $action->cancel();
              }

              $action->success();
              $action->successNotificationTitle('Images deleted successfully');
            }),

          ForceDeleteBulkAction::make()
            ->action(function (Collection $records, Action $action) {
              $isQueued = $records->count() >= self::$numBulkQueue;
              $cdnService = $isQueued ? null : app(CdnService::class);

              foreach ($records as $record) {
                $isQueued
                  ? ForceDeleteGalleryJob::dispatch($record->id)
                  : $cdnService->forceDelete($record->id);
              }

              if ($isQueued) {
                ManageGalleries::_backgroundNotification();
                return $action->cancel();
              }

              $action->success();
              $action->successNotificationTitle('Images deleted successfully');
            }),

          RestoreBulkAction::make()
            ->action(function (Collection $records, Action $action) {
              $isQueued = $records->count() >= self::$numBulkQueue;
              $cdnService = $isQueued ? null : app(CdnService::class);

              foreach ($records as $record) {
                $isQueued
                  ? RestoreGalleryJob::dispatch($record->id)
                  : $cdnService->restore($record->id);
              }

              if ($isQueued) {
                ManageGalleries::_backgroundNotification();
                return $action->cancel();
              }

              $action->success();
              $action->successNotificationTitle('Images restored successfully');
            }),
        ]),
      ]);
  }
}
