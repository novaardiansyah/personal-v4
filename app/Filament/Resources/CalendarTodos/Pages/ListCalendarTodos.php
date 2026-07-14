<?php

namespace App\Filament\Resources\CalendarTodos\Pages;

use App\Filament\Resources\CalendarTodos\CalendarTodoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCalendarTodos extends ListRecords
{
  protected static string $resource = CalendarTodoResource::class;

  protected function getHeaderActions(): array
  {
    return [
      CreateAction::make(),
    ];
  }
}
