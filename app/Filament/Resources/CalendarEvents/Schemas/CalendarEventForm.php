<?php

namespace App\Filament\Resources\CalendarEvents\Schemas;

use App\Enums\RecurrenceType;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CalendarEventForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make()
          ->description('Event Information')
          ->collapsible()
          ->columnSpan(['sm' => 3, 'md' => 2])
          ->schema([
            Grid::make(['sm' => 2, 'xs' => 1])
              ->columnSpanFull()
              ->schema([
                TextInput::make('title')
                  ->required(),
                TextInput::make('location')
                  ->default(null),
                DateTimePicker::make('start_at')
                  ->required()
                  ->native(false),
                DateTimePicker::make('end_at')
                  ->native(false)
                  ->default(null),
                Toggle::make('is_all_day')
                  ->default(false),
                Select::make('category_id')
                  ->relationship('category', 'name')
                  ->native(false)
                  ->preload()
                  ->searchable()
                  ->default(null),
                ColorPicker::make('color')
                  ->default(null),
              ]),
            RichEditor::make('description')
              ->default(null)
              ->columnSpanFull(),
          ]),

        Section::make()
          ->description('Recurrence')
          ->collapsible()
          ->columnSpan(['sm' => 3, 'md' => 1])
          ->columns(1)
          ->schema([
            Select::make('recurrence_type')
              ->options(RecurrenceType::class)
              ->native(false)
              ->preload()
              ->default(null),
            TextInput::make('recurrence_interval')
              ->numeric()
              ->default(null)
              ->minValue(1),
            DateTimePicker::make('recurrence_end_at')
              ->native(false)
              ->default(null),
          ]),

        Section::make()
          ->description('Source')
          ->collapsible()
          ->columnSpan(['sm' => 3, 'md' => 1])
          ->columns(1)
          ->schema([
            TextInput::make('source_type')
              ->label('Source Type')
              ->disabled()
              ->dehydrated(false)
              ->visibleOn('edit'),
            TextInput::make('source_id')
              ->label('Source ID')
              ->numeric()
              ->disabled()
              ->dehydrated(false)
              ->visibleOn('edit'),
          ]),
      ])
      ->columns(3);
  }
}
