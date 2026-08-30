<?php

namespace App\Filament\Resources\DiscordChannels\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DiscordChannelForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make()
          ->description('Channel Information')
          ->collapsible()
          ->columnSpan(['sm' => 3, 'md' => 2])
          ->schema([
            Grid::make(['sm' => 1, 'xs' => 1])
              ->columnSpanFull()
              ->schema([
                Select::make('server_id')
                  ->label('Server')
                  ->relationship('server', 'name')
                  ->searchable()
                  ->preload()
                  ->native(false)
                  ->required(),
                TextInput::make('name')
                  ->label('Name')
                  ->required(),
                TextInput::make('channel_id')
                  ->label('Channel ID')
                  ->copyable(),
                Textarea::make('description')
                  ->label('Description')
                  ->rows(3)
                  ->columnSpanFull(),
              ]),
          ]),
      ])
      ->columns(3);
  }
}
