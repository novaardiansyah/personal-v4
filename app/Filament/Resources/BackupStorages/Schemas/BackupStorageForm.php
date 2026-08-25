<?php

namespace App\Filament\Resources\BackupStorages\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class BackupStorageForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make()
          ->description('Storage Information')
          ->collapsible()
          ->columnSpan(['sm' => 3, 'md' => 2])
          ->schema([
            Grid::make(['sm' => 1, 'xs' => 1])
              ->columnSpanFull()
              ->schema([
                TextInput::make('name')
                  ->label('Name')
                  ->required()
                  ->live(onBlur: true)
                  ->afterStateUpdated(fn(Set $set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
                TextInput::make('slug')
                  ->label('Slug')
                  ->required()
                  ->unique('backup_storages', 'slug', ignoreRecord: true),
                KeyValue::make('keys')
                  ->label('Storage Keys')
                  ->keyLabel('Key')
                  ->valueLabel('Value')
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
              ->visible(fn($record) => $record !== null),
            Toggle::make('active')
              ->label('Active')
              ->default(true),
          ]),
      ])
      ->columns(3);
  }
}
