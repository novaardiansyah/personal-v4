<?php

namespace App\Filament\Resources\Galleries\Pages;

use App\Filament\Resources\Galleries\GalleryResource;
use App\Jobs\GalleryResource\UploadGalleryJob;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\Width;

class ManageGalleries extends ManageRecords
{
  protected static string $resource = GalleryResource::class;

  protected function getHeaderActions(): array
  {
    return [
      CreateAction::make()
        ->label('Upload Image')
        ->modalHeading('Upload image to CDN')
        ->modalWidth(Width::ThreeExtraLarge)
        ->form([
          FileUpload::make('file_path')
            ->label('Image')
            ->image()
            ->disk('public')
            ->directory('images/gallery')
            ->required()
            ->maxSize(10240)
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
        ])
        ->action(function (array $data) {
          $filePath = $data['file_path'];

          foreach ($filePath as $path) {
            UploadGalleryJob::dispatch(
              $path,
              $data['description'] ?? null,
              (bool) ($data['is_private'] ?? false)
            );
          }
        })
        ->successNotification(function (Notification $notification) {
          $notification->title('Background Process')
            ->body('You will see the result in the next page refresh')
            ->success();
        })
    ];
  }

  public static function _backgroundNotification()
  {
    Notification::make()
      ->title('Background Process')
      ->body('You will see the result in the next page refresh')
      ->success()
      ->send();
  }
}
