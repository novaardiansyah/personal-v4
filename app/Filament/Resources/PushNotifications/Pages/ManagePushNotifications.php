<?php

namespace App\Filament\Resources\PushNotifications\Pages;

use App\Filament\Resources\PushNotifications\PushNotificationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;

class ManagePushNotifications extends ManageRecords
{
  protected static string $resource = PushNotificationResource::class;

  protected function getHeaderActions(): array
  {
    return [
      CreateAction::make()
        ->modalWidth(Width::ThreeExtraLarge)
        ->mutateFormDataUsing(function (array $data): array {
          $data['data'] = array_merge($data['data'], [
            'timestamps' => now()->toDateTimeString(),
          ]);

          return $data;
        }),
    ];
  }
}
