<?php

namespace App\Filament\Resources\CalendarTodos\Pages;

use App\Filament\Resources\CalendarTodos\CalendarTodoResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCalendarTodo extends EditRecord
{
  protected static string $resource = CalendarTodoResource::class;

  protected function getHeaderActions(): array
  {
    return [
      ViewAction::make(),
      DeleteAction::make(),
      ForceDeleteAction::make(),
      RestoreAction::make(),
    ];
  }
}
