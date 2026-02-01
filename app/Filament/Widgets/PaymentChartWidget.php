<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Illuminate\Support\Carbon;;

use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Schema;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema;

class PaymentChartWidget extends ChartWidget
{
  use HasFiltersSchema;

  protected ?string $heading = 'Payment Statistics';
  protected int | string | array $columnSpan = 1;
  protected ?string $maxHeight = '600px';

  public function filtersSchema(Schema $schema): Schema
  {
    return $schema->components([
      DatePicker::make('startDate')
        ->label('Start Date')
        ->default(now()->subDays(3))
        ->native(false)
        ->closeOnDateSelection(),
      DatePicker::make('endDate')
        ->label('End Date')
        ->default(now())
        ->native(false)
        ->closeOnDateSelection(),
    ]);
  }

  protected function getData(): array
  {
    $startDate = $this->filters['startDate'] ?? now()->subDays(3);
    $endDate = $this->filters['endDate'] ?? now();

    $startDate = Carbon::parse($startDate);
    $endDate = Carbon::parse($endDate);

    $days = collect();
    $currentDate = $startDate->copy();

    while ($currentDate->lte($endDate)) {
      $days->push($currentDate->format('Y-m-d'));
      $currentDate->addDay();
    }

    $labels = $days->map(function ($date) use ($startDate, $endDate) {
      $dayCount = $startDate->diffInDays($endDate) + 1;

      if ($dayCount > 30) {
        return Carbon::parse($date)->format('M j');
      } elseif ($dayCount > 7) {
        return Carbon::parse($date)->format('M j');
      } else {
        return Carbon::parse($date)->format('D, M j');
      }
    });

    $expenses = $days->map(function ($date) {
      return Payment::where('type_id', 1)
        ->whereDate('date', $date)
        ->sum('amount');
    });

    $incomes = $days->map(function ($date) {
      return Payment::where('type_id', 2)
        ->whereDate('date', $date)
        ->sum('amount');
    });

    return [
      'datasets' => [
        [
          'label'           => 'Expenses',
          'data'            => $expenses->toArray(),
          'borderColor'     => 'rgb(239, 68, 68)',
          'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
          'tension'         => 0.4,
        ],
        [
          'label'           => 'Income',
          'data'            => $incomes->toArray(),
          'borderColor'     => 'rgb(34, 197, 94)',
          'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
          'tension'         => 0.4,
        ],
      ],
      'labels' => $labels->toArray(),
    ];
  }

  protected function getType(): string
  {
    return 'line';
  }
}
