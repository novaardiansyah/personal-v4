<?php

namespace App\Filament\Resources\DiscordServers\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DiscordServerInfolist
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Group::make([
          Section::make('')
            ->description('Server Information')
            ->collapsible()
            ->schema([
              TextEntry::make('name')
                ->label('Name')
                ->placeholder('N/A'),
              TextEntry::make('server_id')
                ->label('Server ID')
                ->badge()
                ->copyable()
                ->color('info')
                ->placeholder('N/A'),
              IconEntry::make('is_active')
                ->label('Active')
                ->boolean(),
              ImageEntry::make('server_icon')
                ->label('Server Icon')
                ->checkFileExistence(false)
                ->imageWidth(50)
                ->imageHeight(50)
                ->circular(),
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
