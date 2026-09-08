<?php

namespace App\Filament\Resources\DiscordChannels\Pages;

use App\Filament\Resources\DiscordChannels\DiscordChannelResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDiscordChannel extends CreateRecord
{
  protected static string $resource = DiscordChannelResource::class;
}
