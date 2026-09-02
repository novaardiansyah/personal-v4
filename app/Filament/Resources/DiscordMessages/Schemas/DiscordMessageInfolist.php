<?php

namespace App\Filament\Resources\DiscordMessages\Schemas;

use App\Models\DiscordMessage;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DiscordMessageInfolist
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Group::make([
          Section::make('')
            ->description('Message Information')
            ->collapsible()
            ->schema([
              TextEntry::make('uid')
                ->label('UID')
                ->badge()
                ->copyable()
                ->color('info')
                ->placeholder('N/A'),
              TextEntry::make('webhook.name')
                ->label('Webhook')
                ->formatStateUsing(fn(DiscordMessage $record) => $record->webhook ? "{$record->webhook->name} (" . ($record->webhook->channel?->name ?? '-') . ")" : '-')
                ->badge()
                ->color('info')
                ->placeholder('N/A'),
              TextEntry::make('status')
                ->label('Status')
                ->badge()
                ->placeholder('N/A'),
              KeyValueEntry::make('content')
                ->label('Content / Embed')
                ->columnSpanFull(),
              KeyValueEntry::make('response')
                ->label('Response Data')
                ->columnSpanFull(),
            ])
            ->columns(3),
        ]),

        Section::make('')
          ->description('Status & Timestamps')
          ->collapsible()
          ->schema([
            TextEntry::make('created_at')
              ->label('Created At')
              ->dateTime()
              ->sinceTooltip(),
            TextEntry::make('updated_at')
              ->label('Last Updated')
              ->dateTime()
              ->sinceTooltip(),
            TextEntry::make('deleted_at')
              ->label('Deleted At')
              ->dateTime()
              ->sinceTooltip()
              ->placeholder('Active'),
          ])
          ->columns(3),
      ])
      ->columns(1);
  }
}
