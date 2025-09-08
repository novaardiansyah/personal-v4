<?php

namespace App\Filament\Resources\ItemTypes\Pages;

use App\Filament\Resources\ItemTypes\ItemTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;

class ManageItemTypes extends ManageRecords
{
  protected static string $resource = ItemTypeResource::class;

  protected function getHeaderActions(): array
  {
    return [
      CreateAction::make()
        ->modalWidth(Width::Medium),
    ];
  }
}
