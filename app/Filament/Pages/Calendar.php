<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use UnitEnum;

class Calendar extends Page
{
  protected static \BackedEnum | string | null $navigationIcon = 'heroicon-o-calendar';
  protected static string | UnitEnum | null $navigationGroup = 'Calendar';
  protected static ?int $navigationSort = 0;
  protected string $view = 'filament.pages.calendar';

  public function getTitle(): string
  {
    return 'Calendar';
  }
}
