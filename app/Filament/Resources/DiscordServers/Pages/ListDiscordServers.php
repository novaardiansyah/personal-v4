<?php

namespace App\Filament\Resources\DiscordServers\Pages;

use App\Filament\Resources\DiscordServers\DiscordServerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDiscordServers extends ListRecords
{
  protected static string $resource = DiscordServerResource::class;

  protected function getHeaderActions(): array
  {
    return [
      CreateAction::make(),
    ];
  }
}
