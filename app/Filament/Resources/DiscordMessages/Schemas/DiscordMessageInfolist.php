<?php

namespace App\Filament\Resources\DiscordMessages\Schemas;

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
              TextEntry::make('status')
                ->label('Status')
                ->badge()
                ->placeholder('N/A'),
              TextEntry::make('content')
                ->label('Content')
                ->placeholder('N/A')
                ->columnSpanFull(),
              TextEntry::make('response')
                ->label('Response')
                ->placeholder('N/A')
                ->columnSpanFull(),
            ])
            ->columns(2),
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
