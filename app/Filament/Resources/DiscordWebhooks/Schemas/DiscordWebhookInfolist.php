<?php

namespace App\Filament\Resources\DiscordWebhooks\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DiscordWebhookInfolist
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Group::make([
          Section::make('')
            ->description('Webhook Information')
            ->collapsible()
            ->schema([
              TextEntry::make('uid')
                ->label('UID')
                ->badge()
                ->copyable()
                ->color('info')
                ->placeholder('N/A'),
              TextEntry::make('channel.name')
                ->label('Channel')
                ->badge()
                ->color('info')
                ->placeholder('N/A'),
              TextEntry::make('name')
                ->label('Name')
                ->placeholder('N/A'),
              TextEntry::make('url')
                ->label('URL')
                ->copyable()
                ->placeholder('N/A')
                ->columnSpanFull(),
              TextEntry::make('description')
                ->label('Description')
                ->placeholder('N/A')
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
