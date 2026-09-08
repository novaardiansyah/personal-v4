<?php

namespace App\Filament\Resources\DiscordChannels\Pages;

use App\Filament\Resources\DiscordChannels\DiscordChannelResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDiscordChannel extends ViewRecord
{
  protected static string $resource = DiscordChannelResource::class;

  protected function getHeaderActions(): array
  {
    return [
      EditAction::make(),
    ];
  }
}
