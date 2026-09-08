<?php

namespace App\Filament\Resources\DiscordServers\RelationManagers;

use App\Filament\Resources\DiscordChannels\DiscordChannelResource;
use App\Filament\Resources\DiscordChannels\Tables\DiscordChannelsTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class ChannelsRelationManager extends RelationManager
{
  protected static string $relationship = 'channels';

  protected static ?string $relatedResource = DiscordChannelResource::class;

  public function table(Table $table): Table
  {
    $table = DiscordChannelsTable::configure($table);

    $columns = $table->getColumns();
    unset($columns['server.name']);

    return $table->columns($columns);
  }
}
