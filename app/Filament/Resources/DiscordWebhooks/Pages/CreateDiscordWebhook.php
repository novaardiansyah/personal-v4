<?php

namespace App\Filament\Resources\DiscordWebhooks\Pages;

use App\Filament\Resources\DiscordWebhooks\DiscordWebhookResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDiscordWebhook extends CreateRecord
{
  protected static string $resource = DiscordWebhookResource::class;
}
