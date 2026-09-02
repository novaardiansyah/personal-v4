<?php

namespace App\Filament\Resources\DiscordMessages\Pages;

use App\Filament\Resources\DiscordMessages\DiscordMessageResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDiscordMessage extends ViewRecord
{
  protected static string $resource = DiscordMessageResource::class;

  protected function getHeaderActions(): array
  {
    return [
      EditAction::make(),
    ];
  }
}
