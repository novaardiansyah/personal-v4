<?php

namespace App\Filament\Resources\DiscordServers\Pages;

use App\Filament\Resources\DiscordServers\DiscordServerResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDiscordServer extends ViewRecord
{
  protected static string $resource = DiscordServerResource::class;

  protected function getHeaderActions(): array
  {
    return [
      EditAction::make(),
    ];
  }
}
