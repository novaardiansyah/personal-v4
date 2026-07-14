<?php

namespace App\Filament\Resources\CalendarTodos\Schemas;

use App\Enums\TodoPriority;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class CalendarTodoForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        TextInput::make('title')
          ->required(),
        RichEditor::make('description')
          ->default(null)
          ->columnSpanFull(),
        Select::make('event_id')
          ->label('Related Event')
          ->relationship('event', 'title')
          ->native(false)
          ->preload()
          ->searchable()
          ->default(null),
        Select::make('priority')
          ->options(TodoPriority::class)
          ->native(false)
          ->preload()
          ->default('medium'),
        DateTimePicker::make('due_at')
          ->native(false)
          ->default(null),
        Toggle::make('is_completed')
          ->label('Completed')
          ->default(false)
          ->afterStateHydrated(function (Toggle $component, $record) {
            $component->state(!is_null($record?->completed_at));
          })
          ->afterStateUpdated(function (Set $set, $state) {
            $set('completed_at', $state ? now() : null);
          }),
        DateTimePicker::make('completed_at')
          ->hidden(),
      ])
      ->columns(2);
  }
}
