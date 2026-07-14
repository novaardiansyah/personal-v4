<?php

namespace App\Filament\Resources\CalendarCategories\Pages;

use App\Filament\Resources\CalendarCategories\CalendarCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCalendarCategory extends CreateRecord
{
  protected static string $resource = CalendarCategoryResource::class;
}
