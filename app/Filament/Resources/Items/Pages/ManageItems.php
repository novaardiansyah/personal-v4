<?php

namespace App\Filament\Resources\Items\Pages;

use App\Filament\Resources\Items\ItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;

class ManageItems extends ManageRecords
{
  protected static string $resource = ItemResource::class;

  protected function getHeaderActions(): array
  {
    return [
      CreateAction::make()
        ->modalWidth(Width::Medium)
        ->mutateDataUsing(function (array $data) {
          $data['code'] = getCode('item');
          return $data;
        }),
    ];
  }
}
