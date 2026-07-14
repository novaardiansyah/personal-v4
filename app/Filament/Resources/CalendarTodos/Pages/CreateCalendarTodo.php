<?php

namespace App\Filament\Resources\CalendarTodos\Pages;

use App\Filament\Resources\CalendarTodos\CalendarTodoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCalendarTodo extends CreateRecord
{
  protected static string $resource = CalendarTodoResource::class;
}
