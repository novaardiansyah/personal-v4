<?php

/*
 * Project Name: personal-v4
 * File: PaymentCategoryChartWidget.php
 * Created Date: Sunday March 15th 2026
 * 
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 * 
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

namespace App\Filament\Widgets;

use App\Models\Payment;
use App\Models\PaymentCategory;
use Illuminate\Support\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Schema;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema;

class PaymentCategoryChartWidget extends ChartWidget
{
  use HasFiltersSchema;

  protected ?string $heading = 'Transaction by Category';
  protected int|string|array $columnSpan = 1;
  protected ?string $maxHeight = '400px';

  public function filtersSchema(Schema $schema): Schema
  {
    return $schema->components([
      DatePicker::make('startDate')
        ->label('Start Date')
        ->default(now()->startOfMonth())
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
    $startDate = $this->filters['startDate'] ?? now()->startOfMonth();
    $endDate = $this->filters['endDate'] ?? now();

    $startDate = Carbon::parse($startDate);
    $endDate = Carbon::parse($endDate);

    $categories = PaymentCategory::where('user_id', auth()->id())
      ->orWhere('is_default', true)
      ->get();

    $data = [];
    $labels = [];
    $backgroundColor = [];

    $colors = [
      'rgb(239, 68, 68)',
      'rgb(59, 130, 246)',
      'rgb(34, 197, 94)',
      'rgb(234, 179, 8)',
      'rgb(168, 85, 247)',
      'rgb(249, 115, 22)',
      'rgb(6, 182, 212)',
      'rgb(236, 72, 153)',
      'rgb(100, 116, 139)',
      'rgb(20, 184, 166)',
    ];

    $index = 0;
    foreach ($categories as $category) {
      $amount = Payment::where('category_id', $category->id)
        ->where('type_id', 1)
        ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
        ->sum('amount');

      if ($amount > 0) {
        $data[] = $amount;
        $labels[] = $category->name;
        $backgroundColor[] = $colors[$index % count($colors)];
        $index++;
      }
    }

    $uncategorizedAmount = Payment::whereNull('category_id')
      ->where('type_id', 1)
      ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
      ->sum('amount');

    if ($uncategorizedAmount > 0) {
      $data[] = $uncategorizedAmount;
      $labels[] = 'Uncategorized';
      $backgroundColor[] = 'rgb(156, 163, 175)';
    }

    return [
      'datasets' => [
        [
          'label'           => 'Expense',
          'data'            => $data,
          'backgroundColor' => $backgroundColor,
        ],
      ],
      'labels' => $labels,
    ];
  }

  protected function getType(): string
  {
    return 'doughnut';
  }
}
