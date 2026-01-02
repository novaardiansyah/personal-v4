<?php

namespace App\Filament\Resources\Items\Pages;

use App\Filament\Resources\Items\ItemResource;
use App\Models\Item;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;
use Filament\Notifications\Notification;

class ManageItems extends ManageRecords
{
  protected static string $resource = ItemResource::class;

  protected function getHeaderActions(): array
  {
    return [
      CreateAction::make()
        ->modalWidth(Width::Medium)
        ->mutateDataUsing(function (array $data, CreateAction $action) {
          $item = Item::where('name', $data['name'])->first();

          if ($item) {
            Notification::make()
              ->title('Product or Service already exists!')
              ->danger()
              ->send();
            
            $action->halt();
          }

          $data['code'] = getCode('item');
          return $data;
        }),
    ];
  }
}
