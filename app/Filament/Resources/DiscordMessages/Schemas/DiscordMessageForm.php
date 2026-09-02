<?php

namespace App\Filament\Resources\DiscordMessages\Schemas;

use App\Enums\DiscordMessageStatus;
use App\Models\DiscordWebhook;
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
                Select::make('webhook_id')
                  ->label('Webhook')
                  ->relationship('webhook', 'name', fn($query) => $query->with('channel'))
                  ->getOptionLabelFromRecordUsing(fn(DiscordWebhook $record) => "{$record->name} (" . ($record->channel?->name ?? '-') . ")")
                  ->searchable()
                  ->preload()
                  ->native(false)
                  ->required(),
                Textarea::make('content')
                  ->label('Content / Embed Payload')
                  ->rows(6)
                  ->formatStateUsing(fn($state) => toJsonPretty($state))
                  ->mutateDehydratedStateUsing(function ($state) {
                    if (empty($state)) {
                      return null;
                    }

                    $decoded = json_decode((string) $state, true);
                    return json_last_error() === JSON_ERROR_NONE ? $decoded : ['content' => $state];
                  })
                  ->columnSpanFull(),
                Textarea::make('response')
                  ->label('Response Data')
                  ->rows(6)
                  ->disabled()
                  ->formatStateUsing(fn($state) => toJsonPretty($state))
                  ->visible(fn($record) => $record !== null)
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
            Select::make('status')
              ->label('Status')
              ->options(DiscordMessageStatus::class)
              ->default(DiscordMessageStatus::Pending)
              ->required()
              ->native(false),
          ]),
      ])
      ->columns(3);
  }
}
