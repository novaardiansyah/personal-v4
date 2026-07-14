<?php

namespace App\Filament\Resources\CalendarTodos\RelationManagers;

use App\Filament\Resources\CalendarEvents\Tables\CalendarEventsTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class EventRelationManager extends RelationManager
{
  protected static string $relationship = 'event';

  public function table(Table $table): Table
  {
    $table = CalendarEventsTable::configure($table);

    $columns = $table->getColumns();
    unset($columns['source_link']);

    return $table
      ->columns($columns)
      ->defaultSort('start_at', 'desc');
  }
}
