<?php

namespace App\Filament\Resources\DiscordServers\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DiscordServerForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make()
          ->description('Server Information')
          ->collapsible()
          ->columnSpan(['sm' => 3, 'md' => 2])
          ->schema([
            Grid::make(['sm' => 1, 'xs' => 1])
              ->columnSpanFull()
              ->schema([
                TextInput::make('name')
                  ->label('Name')
                  ->required(),
                TextInput::make('server_id')
                  ->label('Server ID')
                  ->copyable(),
                TextInput::make('server_icon')
                  ->label('Server Icon'),
                Textarea::make('description')
                  ->label('Description')
                  ->rows(3)
                  ->columnSpanFull(),
              ]),
          ]),

        Section::make()
          ->description('Status & Metadata')
          ->collapsible()
          ->columnSpan(['sm' => 3, 'md' => 1])
          ->schema([
            Toggle::make('is_active')
              ->label('Active')
              ->default(true),
          ]),
      ])
      ->columns(3);
  }
}
