<?php

namespace App\Filament\Resources\CalendarCategories\Pages;

use App\Filament\Resources\CalendarCategories\CalendarCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCalendarCategories extends ManageRecords
{
  protected static string $resource = CalendarCategoryResource::class;

  protected function getHeaderActions(): array
  {
    return [
      CreateAction::make(),
    ];
  }
}
