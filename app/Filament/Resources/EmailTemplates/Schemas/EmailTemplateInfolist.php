<?php

namespace App\Filament\Resources\EmailTemplates\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EmailTemplateInfolist
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make()
          ->description('Email template information')
          ->columnSpan(2)
          ->collapsible()
          ->schema([
            TextEntry::make('subject')
              ->label('Subject'),
            TextEntry::make('message')
              ->label('Message')
              ->html(),
            KeyValueEntry::make('placeholders')
              ->label('Placeholders'),
          ]),

        Section::make()
          ->description('Status Information')
          ->columnSpan(1)
          ->columns(2)
          ->collapsible()
          ->schema([
            TextEntry::make('code')
              ->label('Template ID')
              ->badge()
              ->copyable(),
            TextEntry::make('alias')
              ->label('Alias')
              ->badge()
              ->copyable(),
            IconEntry::make('is_protected')
              ->label('Protected'),
            TextEntry::make('notes')
              ->label('Notes')
              ->columnSpanFull(),
          ]),

        Section::make()
          ->description('Timestamps information')
          ->columnSpanFull()
          ->columns(3)
          ->collapsible()
          ->schema([
            TextEntry::make('created_at')
              ->label('Created At')
              ->dateTime()
              ->sinceTooltip(),
            TextEntry::make('updated_at')
              ->label('Updated At')
              ->dateTime()
              ->sinceTooltip(),
            TextEntry::make('deleted_at')
              ->label('Deleted At')
              ->dateTime()
              ->sinceTooltip(),
          ]),
      ])
      ->columns(3);
  }
}
