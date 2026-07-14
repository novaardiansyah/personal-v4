<?php

namespace App\Filament\Resources\CalendarEvents\Pages;

use App\Filament\Resources\CalendarEvents\CalendarEventResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCalendarEvents extends ListRecords
{
  protected static string $resource = CalendarEventResource::class;

  protected function getHeaderActions(): array
  {
    return [
      CreateAction::make(),
    ];
  }
}
