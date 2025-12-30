<?php

namespace App\Filament\Resources\Galleries\Pages;

use App\Filament\Resources\Galleries\GalleryResource;
use App\Services\GalleryResource\CdnService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
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
      Action::make('upload')
        ->modalWidth(Width::ThreeExtraLarge)
        ->label('Upload Image')
        ->modalHeading('Upload image to CDN')
        ->schema([
          FileUpload::make('file_path')
            ->label('Image')
            ->image()
            ->disk('public')
            ->directory('images/gallery')
            ->required()
            ->maxSize(10240)
            ->imageEditor()
            ->columnSpanFull(),
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
        ->action(function (Action $action, array $data) {
          $filePath = $data['file_path'];

          $response = app(CdnService::class)->upload(
            $filePath,
            $data['description'] ?? null,
            (bool) ($data['is_private'] ?? false)
          );

          Storage::disk('public')->delete($filePath);

          if ($response->successful()) {
            $action->success();
            $action->successNotificationTitle('Image uploaded successfully');
          } else {
            $action->failure();
            $action->failureNotificationTitle('Failed to upload image');
          }
        })
    ];
  }
}
