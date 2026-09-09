<?php

namespace App\Filament\Resources\DiscordMessages\Schemas;

use App\Models\DiscordMessage;
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
              TextEntry::make('webhook.name')
                ->label('Webhook')
                ->formatStateUsing(fn(DiscordMessage $record) => $record->webhook ? "{$record->webhook->name} (" . ($record->webhook->channel?->name ?? '-') . ")" : '-')
                ->badge()
                ->color('info')
                ->placeholder('N/A'),
              TextEntry::make('status')
                ->label('Status')
                ->badge()
                ->placeholder('N/A'),
              TextEntry::make('count_retry')
                ->label('Retry Count')
                ->badge()
                ->placeholder('0'),
              TextEntry::make('content')
                ->label('Content / Embed')
                ->state(fn(?DiscordMessage $record) => filled($record?->content) ? toJsonPretty($record->content) : null)
                ->formatStateUsing(fn($state) => formatJsonPre($state))
                ->html()
                ->copyable()
                ->copyableState(fn($state) => $state)
                ->placeholder('-')
                ->columnSpanFull(),
              TextEntry::make('response')
                ->label('Response Data')
                ->state(fn(?DiscordMessage $record) => filled($record?->response) ? toJsonPretty($record->response) : null)
                ->formatStateUsing(fn($state) => formatJsonPre($state))
                ->html()
                ->copyable()
                ->copyableState(fn($state) => $state)
                ->placeholder('-')
                ->columnSpanFull(),
            ])
            ->columns(4),
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
