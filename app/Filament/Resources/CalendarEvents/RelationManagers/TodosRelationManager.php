<?php

namespace App\Filament\Resources\CalendarEvents\RelationManagers;

use App\Filament\Resources\CalendarTodos\Tables\CalendarTodosTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class TodosRelationManager extends RelationManager
{
  protected static string $relationship = 'todos';

  public function table(Table $table): Table
  {
    $table = CalendarTodosTable::configure($table);

    $columns = $table->getColumns();
    unset($columns['event.title']);

    return $table
      ->columns($columns)
      ->defaultSort('created_at', 'desc');
  }
}
