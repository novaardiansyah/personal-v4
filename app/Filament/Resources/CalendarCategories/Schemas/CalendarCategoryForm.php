<?php

namespace App\Filament\Resources\CalendarCategories\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CalendarCategoryForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        TextInput::make('name')
          ->required(),
        ColorPicker::make('color')
          ->required()
          ->default('#3B82F6'),
        Toggle::make('is_default')
          ->default(false),
      ])
      ->columns(1);
  }
}
