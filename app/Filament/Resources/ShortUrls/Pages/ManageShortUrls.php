<?php

namespace App\Filament\Resources\ShortUrls\Pages;

use App\Filament\Resources\ShortUrls\ShortUrlResource;
use App\Models\ShortUrl;
use App\Filament\Resources\ShortUrls\Schemas\ShortUrlAction;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;
use Str;

class ManageShortUrls extends ManageRecords
{
  protected static string $resource = ShortUrlResource::class;

  protected function getHeaderActions(): array
  {
    return [
      CreateAction::make()
        ->modalWidth(Width::Medium)
        ->mutateDataUsing(function (array $data) {
          $maxAttempts     = 3;
          $attempts        = 0;
          $uniqueCodeFound = false;
          $str             = $data['str_code'];

          while (!$uniqueCodeFound && $attempts < $maxAttempts) {
            $exist = ShortUrl::where('short_code', $str)->first();

            if (!$exist) {
              $uniqueCodeFound = true;
            } else {
              $attempts++;
              if ($attempts < $maxAttempts) {
                $str = Str::random(7);
              }
            }
          }

          if (!$uniqueCodeFound) {
            Notification::make()
              ->warning()
              ->title('Failed to generate unique code')
              ->body('Unable to generate a unique short code after 3 attempts. Please try again.')
              ->send();

            $this->halt();
          }

          $data['code']       = getCode('short_url');
          $data['short_code'] = $str;
          $data['str_code']   = $str;

          return $data;
        })
        ->after(function (ShortUrl $record) {
          ShortUrlAction::generateQRCode($record);
        }),
    ];
  }
}
