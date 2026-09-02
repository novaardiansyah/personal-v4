<?php

namespace App\Filament\Resources\DiscordMessages\Schemas;

use App\Enums\DiscordMessageStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DiscordMessageForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make()
          ->description('Message Information')
          ->collapsible()
          ->columnSpan(['sm' => 3, 'md' => 2])
          ->schema([
            Grid::make(['sm' => 1, 'xs' => 1])
              ->columnSpanFull()
              ->schema([
                Textarea::make('content')
                  ->label('Content')
                  ->rows(4)
                  ->required()
                  ->columnSpanFull(),
                Textarea::make('response')
                  ->label('Response')
                  ->rows(4)
                  ->columnSpanFull(),
              ]),
          ]),

        Section::make()
          ->description('Status & Metadata')
          ->collapsible()
          ->columnSpan(['sm' => 3, 'md' => 1])
          ->schema([
            Select::make('status')
              ->label('Status')
              ->options(DiscordMessageStatus::class)
              ->default(DiscordMessageStatus::Pending)
              ->required()
              ->native(false),
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
