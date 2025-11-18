<?php

namespace App\Filament\Resources\ShortUrls\Pages;

use App\Filament\Resources\ShortUrls\ShortUrlResource;
use App\Models\ShortUrl;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;

class ManageShortUrls extends ManageRecords
{
  protected static string $resource = ShortUrlResource::class;

  protected function getHeaderActions(): array
  {
    return [
      CreateAction::make()
        ->modalWidth(Width::Medium)
        ->mutateFormDataUsing(function (array $data) {
          $uniqueCode = ShortUrl::generateUniqueShortCode();

          if (!$uniqueCode) {
            Notification::make()
              ->warning()
              ->title('Failed to generate unique code')
              ->body('Unable to generate a unique short code after 3 attempts. Please try again.')
              ->send();

            $this->halt();
          }

          $data['code']       = getCode('short_url');
          $data['short_code'] = $uniqueCode;
          $data['str_code']   = $uniqueCode;

          return $data;
        })
        ->after(function (ShortUrl $record) {
          $record->generateQRCode();
        }),
    ];
  }
}
