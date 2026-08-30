<?php

namespace App\Filament\Resources\DiscordChannels\RelationManagers;

use App\Filament\Resources\DiscordWebhooks\DiscordWebhookResource;
use App\Filament\Resources\DiscordWebhooks\Tables\DiscordWebhooksTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class WebhooksRelationManager extends RelationManager
{
  protected static string $relationship = 'webhooks';

  protected static ?string $relatedResource = DiscordWebhookResource::class;

  public function table(Table $table): Table
  {
    $table = DiscordWebhooksTable::configure($table);

    $columns = $table->getColumns();
    unset($columns['channel.name']);

    return $table->columns($columns);
  }
}
