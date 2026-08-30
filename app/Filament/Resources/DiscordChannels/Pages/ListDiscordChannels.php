<?php

namespace App\Filament\Resources\DiscordChannels\Pages;

use App\Filament\Resources\DiscordChannels\DiscordChannelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDiscordChannels extends ListRecords
{
  protected static string $resource = DiscordChannelResource::class;

  protected function getHeaderActions(): array
  {
    return [
      CreateAction::make(),
    ];
  }
}
