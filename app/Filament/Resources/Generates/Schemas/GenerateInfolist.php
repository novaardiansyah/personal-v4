<?php

namespace App\Filament\Resources\Generates\Schemas;

use App\Models\Generate;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GenerateInfolist
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make()
          ->description('Detail generate information')
          ->columns(3)
          ->columnSpan(2)
          ->collapsible()
          ->schema([
            TextEntry::make('name'),
            TextEntry::make('alias')
              ->copyable()
              ->badge()
              ->color('info'),
            TextEntry::make('prefix')
              ->badge()
              ->color('info'),
            TextEntry::make('separator')
              ->badge()
              ->color('info'),
            TextEntry::make('queue')
              ->badge()
              ->color('info')
              ->numeric(),
            TextEntry::make('preview')
              ->copyable()
              ->badge()
              ->color('info')
              ->state(fn(Generate $record) => $record->getNextId()),
          ]),

        Section::make()
          ->description('Timestamp information')
          ->columns(3)
          ->collapsible()
          ->schema([
            TextEntry::make('created_at')
              ->dateTime()
              ->sinceTooltip(),
            TextEntry::make('updated_at')
              ->dateTime()
              ->sinceTooltip(),
            TextEntry::make('deleted_at')
              ->dateTime()
              ->sinceTooltip(),
          ])
      ])
      ->columns(3);
  }
}
