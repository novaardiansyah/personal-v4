<?php

namespace App\Filament\Resources\DiscordMessages\Pages;

use App\Filament\Resources\DiscordMessages\DiscordMessageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDiscordMessages extends ListRecords
{
  protected static string $resource = DiscordMessageResource::class;

  protected function getHeaderActions(): array
  {
    return [
      CreateAction::make(),
    ];
  }
}
