<?php

namespace App\Filament\Resources\DiscordWebhooks\Pages;

use App\Filament\Resources\DiscordWebhooks\DiscordWebhookResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDiscordWebhooks extends ListRecords
{
  protected static string $resource = DiscordWebhookResource::class;

  protected function getHeaderActions(): array
  {
    return [
      CreateAction::make(),
    ];
  }
}
