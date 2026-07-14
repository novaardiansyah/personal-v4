<?php

namespace App\Filament\Resources\CalendarTodos\Pages;

use App\Filament\Resources\CalendarTodos\CalendarTodoResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCalendarTodo extends ViewRecord
{
  protected static string $resource = CalendarTodoResource::class;

  protected function getHeaderActions(): array
  {
    return [
      EditAction::make(),
      DeleteAction::make(),
      ForceDeleteAction::make(),
      RestoreAction::make(),
    ];
  }
}
