<?php

namespace App\Filament\Resources\BackupStorages\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BackupStorageInfolist
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Group::make([
          Section::make('')
            ->description('Storage Information')
            ->collapsible()
            ->schema([
              TextEntry::make('uid')
                ->label('UID')
                ->copyable()
                ->badge()
                ->color('info'),
              TextEntry::make('name')
                ->label('Name')
                ->placeholder('N/A'),
              TextEntry::make('slug')
                ->label('Slug')
                ->badge()
                ->copyable()
                ->placeholder('N/A'),
							IconEntry::make('active')
								->label('Active')
								->boolean(),
              KeyValueEntry::make('keys')
                ->label('Storage Keys')
                ->keyLabel('Key')
                ->valueLabel('Value')
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
          ->columns(3)
      ])
      ->columns(1);
  }
}
