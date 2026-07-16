<?php

namespace App\Filament\Resources\CalendarTodos\Pages;

use App\Filament\Resources\CalendarTodos\CalendarTodoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCalendarTodo extends CreateRecord
{
  protected static string $resource = CalendarTodoResource::class;

  protected function getRedirectUrl(): string
  {
    return url('/admin/calendar');
  }

  protected function afterFill(): void
  {
    if ($dueAt = request()->query('due_at')) {
      $this->data['due_at'] = $dueAt;
    }
  }
}
