<?php

namespace App\Filament\Resources\Galleries\Pages;

use App\Filament\Resources\Galleries\GalleryResource;
use App\Jobs\GalleryResource\UploadGalleryJob;
use App\Services\GalleryResource\CdnService;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Storage;

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
        ])
        ->action(function (CreateAction $action, array $data) {
          $filePath = $data['file_path'];
          $isQueued = count($filePath) > 3;

          foreach ($filePath as $path) {
            if ($isQueued) {
              UploadGalleryJob::dispatch(
                $path,
                $data['description'] ?? null,
                (bool) ($data['is_private'] ?? false)
              );
            } else {
              app(CdnService::class)->upload(
                $path,
                $data['description'] ?? null,
                (bool) ($data['is_private'] ?? false)
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
