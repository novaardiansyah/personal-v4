<?php

namespace App\Filament\Widgets;

use App\Models\Subscription;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Carbon;

class UpcomingSubscriptionsWidget extends TableWidget
{
  protected static ?string $heading = 'Upcoming Subscriptions';
  protected int | string | array $columnSpan = 'full';

  public function table(Table $table): Table
  {
    $startDate = Carbon::now()->startOfDay();
    $endDate = Carbon::now()->addDays(7)->endOfDay();

    return $table
      ->query(
        Subscription::query()
          ->where('is_paused', false)
          ->whereNotNull('next_date')
          ->whereBetween('next_date', [$startDate, $endDate])
      )
      ->columns([
        TextColumn::make('name')
          ->label('Name')
          ->searchable()
          ->limit(30),
        TextColumn::make('amount')
          ->label('Amount')
          ->money('IDR')
          ->sortable(),
        TextColumn::make('cycle')
          ->label('Cycle')
          ->badge()
          ->formatStateUsing(fn($state) => ucfirst($state)),
        TextColumn::make('next_date')
          ->label('Due Date')
          ->date('d M Y')
          ->sortable()
          ->sinceTooltip(),
      ])
      ->paginated(false)
      ->defaultSort('next_date', 'asc')
      ->recordUrl(
        fn($record) => url("/admin/subscriptions/{$record->id}/edit"),
        true
      );
  }
}
