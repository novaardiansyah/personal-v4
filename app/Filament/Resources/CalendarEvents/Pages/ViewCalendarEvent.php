<?php

namespace App\Filament\Resources\CalendarEvents\Pages;

use App\Filament\Resources\CalendarEvents\CalendarEventResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCalendarEvent extends ViewRecord
{
  protected static string $resource = CalendarEventResource::class;

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
