<?php

namespace App\Filament\Resources\DiscordMessages\Pages;

use App\Filament\Resources\DiscordMessages\DiscordMessageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDiscordMessage extends CreateRecord
{
  protected static string $resource = DiscordMessageResource::class;
}
