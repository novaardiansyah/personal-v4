<?php

namespace App\Filament\Resources\Galleries\Pages;

use App\Filament\Resources\Galleries\Actions\GalleryAction;
use App\Filament\Resources\Galleries\GalleryResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManageGalleries extends ManageRecords
{
  protected static string $resource = GalleryResource::class;

  protected function getHeaderActions(): array
  {
    return [
      GalleryAction::upload(),
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
