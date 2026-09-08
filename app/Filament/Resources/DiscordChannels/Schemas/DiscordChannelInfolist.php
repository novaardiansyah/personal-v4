<?php

namespace App\Filament\Resources\DiscordChannels\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DiscordChannelInfolist
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Group::make([
          Section::make('')
            ->description('Channel Information')
            ->collapsible()
            ->schema([
              TextEntry::make('uid')
                ->label('UID')
                ->badge()
                ->copyable()
                ->color('info')
                ->placeholder('N/A'),
              TextEntry::make('server.name')
                ->label('Server')
                ->badge()
                ->color('info')
                ->placeholder('N/A'),
              TextEntry::make('name')
                ->label('Name')
                ->placeholder('N/A'),
              TextEntry::make('channel_id')
                ->label('Channel ID')
                ->badge()
                ->copyable()
                ->color('info')
                ->placeholder('N/A'),
              TextEntry::make('description')
                ->label('Description')
                ->placeholder('N/A')
                ->columnSpan(2),
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
