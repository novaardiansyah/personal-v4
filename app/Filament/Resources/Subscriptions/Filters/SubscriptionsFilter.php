<?php

namespace App\Filament\Resources\Subscriptions\Filters;

use Filament\Tables\Filters\SelectFilter;

class SubscriptionsFilter
{
  public static function status(): SelectFilter
  {
    return SelectFilter::make('is_paused')
      ->label('Status')
      ->options([
        false => 'Active',
        true  => 'Paused',
      ])
      ->default(false)
      ->native(false);
  }
}