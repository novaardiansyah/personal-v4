<?php

namespace App\Filament\Resources\DiscordWebhooks\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DiscordWebhookForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make()
          ->description('Webhook Information')
          ->collapsible()
          ->columnSpan(['sm' => 3, 'md' => 2])
          ->schema([
            Grid::make(['sm' => 1, 'xs' => 1])
              ->columnSpanFull()
              ->schema([
                Select::make('channel_id')
                  ->label('Channel')
                  ->relationship('channel', 'name')
                  ->searchable()
                  ->preload()
                  ->native(false)
                  ->required(),
                TextInput::make('name')
                  ->label('Name')
                  ->required(),
                TextInput::make('url')
                  ->label('URL')
                  ->url()
                  ->copyable()
                  ->required(),
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
            TextInput::make('uid')
              ->label('UID')
              ->disabled()
              ->dehydrated(false)
              ->visible(fn($record) => $record !== null)
              ->copyable(),
          ]),
      ])
      ->columns(3);
  }
}
