<?php

namespace App\Filament\Resources\CalendarEvents\Pages;

use App\Filament\Resources\CalendarEvents\CalendarEventResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCalendarEvent extends CreateRecord
{
  protected static string $resource = CalendarEventResource::class;

  protected function getRedirectUrl(): string
  {
    return url('/admin/calendar');
  }

  protected function afterFill(): void
  {
    if ($startAt = request()->query('start_at')) {
      $this->data['start_at'] = $startAt;
    }
  }
}
