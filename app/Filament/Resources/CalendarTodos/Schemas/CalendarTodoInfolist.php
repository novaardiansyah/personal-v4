<?php

namespace App\Filament\Resources\CalendarTodos\Schemas;

use App\Enums\TodoPriority;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CalendarTodoInfolist
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make('')
          ->description('Todo Information')
          ->schema([
            TextEntry::make('code')
              ->copyable()
              ->badge()
              ->color('info'),
            TextEntry::make('title')
              ->placeholder('N/A'),
            TextEntry::make('priority')
              ->badge()
              ->color(fn(TodoPriority $state): string => $state->color())
              ->formatStateUsing(fn(TodoPriority $state): string => $state->label()),
            TextEntry::make('due_at')
              ->dateTime('M d, Y H:i')
              ->placeholder('No due date'),
            IconEntry::make('completed_at')
              ->label('Completed')
              ->boolean()
              ->getStateUsing(fn($record) => !is_null($record->completed_at)),
            TextEntry::make('event.title')
              ->label('Related Event')
              ->placeholder('None'),
            TextEntry::make('description')
              ->markdown()
              ->prose()
              ->columnSpanFull()
              ->placeholder('No description'),
          ])
          ->columns(['xl' => 3, '2xl' => 4])
          ->columnSpan(['sm' => 3, 'md' => 2]),

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
