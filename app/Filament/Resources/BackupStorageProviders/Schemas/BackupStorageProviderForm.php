<?php

namespace App\Filament\Resources\BackupStorageProviders\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class BackupStorageProviderForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make()
          ->description('Provider Information')
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
                  ->unique('backup_storage_providers', 'slug', ignoreRecord: true),
              ]),
          ]),

        Section::make()
          ->description('Metadata')
          ->collapsible()
          ->columnSpan(['sm' => 3, 'md' => 1])
          ->schema([
            TextInput::make('uid')
              ->label('UID')
              ->disabled()
              ->dehydrated(false)
              ->visible(fn($record) => $record !== null),
          ]),
      ])
      ->columns(3);
  }
}
