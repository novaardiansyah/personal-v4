<?php

namespace App\Filament\Resources\CalendarEvents\Schemas;

use App\Enums\RecurrenceType;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CalendarEventInfolist
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make('')
          ->description('Event Information')
          ->schema([
            TextEntry::make('code')
              ->copyable()
              ->badge()
              ->color('info'),
            TextEntry::make('title')
              ->placeholder('N/A'),
            TextEntry::make('location')
              ->placeholder('N/A'),
            TextEntry::make('start_at')
              ->dateTime('M d, Y H:i'),
            TextEntry::make('end_at')
              ->dateTime('M d, Y H:i')
              ->placeholder('N/A'),
            IconEntry::make('is_all_day')
              ->boolean(),
            TextEntry::make('category.name')
              ->label('Category')
              ->placeholder('N/A'),
            TextEntry::make('color')
              ->label('Color')
              ->formatStateUsing(
                fn(?string $state): string =>
                $state
                  ? "<span style='display: inline-flex; align-items: center; gap: 0.5rem;'>
                      <span style='width: 1rem; height: 1rem; border-radius: 50%; background-color: {$state}; border: 1px solid #e5e7eb;'></span>
                      <span>{$state}</span>
                    </span>"
                  : 'N/A'
              )
              ->html()
              ->copyable(),
            TextEntry::make('description')
              ->markdown()
              ->prose()
              ->columnSpanFull()
              ->placeholder('No description'),
          ])
          ->columns(['xl' => 3, '2xl' => 4])
          ->columnSpan(['sm' => 3, 'md' => 2]),

        Section::make('')
          ->description('Recurrence')
          ->schema([
            TextEntry::make('recurrence_type')
              ->label('Type')
              ->formatStateUsing(fn(?string $state) => $state ? RecurrenceType::from($state)->label() : 'None'),
            TextEntry::make('recurrence_interval')
              ->label('Interval'),
            TextEntry::make('recurrence_end_at')
              ->label('End At')
              ->dateTime('M d, Y H:i')
              ->placeholder('Never'),
          ])
          ->columns(1)
          ->columnSpan(['sm' => 3, 'md' => 1]),

        Section::make('')
          ->description('System Information')
          ->schema([
            TextEntry::make('created_at')
              ->label('Created At')
              ->dateTime(),
            TextEntry::make('updated_at')
              ->label('Last Updated')
              ->dateTime()
              ->sinceTooltip(),
            TextEntry::make('deleted_at')
              ->label('Deleted At')
              ->dateTime()
              ->placeholder('Active'),
          ])
          ->columns(1)
          ->columnSpan(['sm' => 3, 'md' => 1]),
      ])
      ->columns(3);
  }
}
