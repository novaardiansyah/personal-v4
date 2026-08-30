<?php

namespace App\Filament\Resources\DiscordWebhooks\Pages;

use App\Filament\Resources\DiscordWebhooks\DiscordWebhookResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDiscordWebhook extends ViewRecord
{
  protected static string $resource = DiscordWebhookResource::class;

  protected function getHeaderActions(): array
  {
    return [
      EditAction::make(),
    ];
  }
}
