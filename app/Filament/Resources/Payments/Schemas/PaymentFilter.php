<?php

namespace App\Filament\Resources\Payments\Schemas;

use Illuminate\Support\Carbon;

use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class PaymentFilter
{
  public static function paymentAccount(): SelectFilter
  {
    return SelectFilter::make('payment_account_id')
      ->label('Payment')
      ->relationship('payment_account', 'name')
      ->native(false)
      ->searchable()
      ->preload();
  }

  public static function paymentAccountTo(): SelectFilter
  {
    return SelectFilter::make('payment_account_to_id')
      ->label('Payment To')
      ->relationship('payment_account_to', 'name')
      ->native(false)
      ->searchable()
      ->preload();
  }

  public static function category(): SelectFilter
  {
    return SelectFilter::make('category_id')
      ->label('Category')
      ->relationship('category', 'name', fn($query) => $query->where('user_id', auth()->id()))
      ->native(false)
      ->searchable()
      ->preload();
  }

  public static function date(): Filter
  {
    return Filter::make('date')
      ->schema([
        DatePicker::make('date_from')
          ->native(false)
          ->displayFormat('M d, Y')
          ->closeOnDateSelection(),
        DatePicker::make('date_until')
          ->native(false)
          ->displayFormat('M d, Y')
          ->closeOnDateSelection(),
      ])
      ->query(function (Builder $query, array $data): Builder {
        return $query
          ->when(
            $data['date_from'],
            fn(Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
          )
          ->when(
            $data['date_until'],
            fn(Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
          );
      })
      ->indicateUsing(function (array $data): ?string {
        if (!$data['date_from'] && !$data['date_until'])
          return null;

        if ($data['date_from'] && $data['date_until'])
          return 'Date From: ' . Carbon::parse($data['date_from'])->toFormattedDateString() . ' - Date Until: ' . Carbon::parse($data['date_until'])->toFormattedDateString();

        if ($data['date_from'])
          return 'Date From: ' . Carbon::parse($data['date_from'])->toFormattedDateString();

        return 'Date Until: ' . Carbon::parse($data['date_until'])->toFormattedDateString();
      });
  }
}
