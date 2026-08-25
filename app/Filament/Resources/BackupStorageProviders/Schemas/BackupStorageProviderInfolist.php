<?php

namespace App\Filament\Resources\BackupStorageProviders\Schemas;

use App\Models\BackupStorageProvider;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BackupStorageProviderInfolist
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Group::make([
          Section::make('')
            ->description('Provider Information')
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
            ])
            ->columns(2),
        ])
          ->columnSpan(['default' => 3, '2xl' => 2]),

        Section::make('')
          ->description('Timestamps')
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
              ->visible(fn(BackupStorageProvider $record): bool => $record->trashed())
              ->placeholder('Active'),
          ])
          ->columns(1)
          ->columnSpan(['default' => 3, '2xl' => 1]),
      ])
      ->columns(['default' => 1, '2xl' => 3]);
  }
}
