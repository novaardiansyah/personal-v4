<?php

namespace App\Filament\Resources\UptimeMonitors\Widgets;

use App\Models\UptimeMonitor;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;

class UptimeStatusChart extends ChartWidget
{
  public ?Model $record = null;

  protected static bool $isDiscovered = false;

  protected ?string $heading = 'Health Distribution';

  protected ?string $maxHeight = '300px';

  protected function getData(): array
  {
    $record = $this->record;

    if (! $record instanceof UptimeMonitor) {
      return ['datasets' => [], 'labels' => []];
    }

    $healthyChecks = $record->healthy_checks ?? 0;
    $unhealthyChecks = $record->unhealthy_checks ?? 0;

    return [
      'datasets' => [
        [
          'data'            => [$healthyChecks, $unhealthyChecks],
          'backgroundColor' => ['rgba(34, 197, 94, 0.8)', 'rgba(239, 68, 68, 0.8)'],
          'borderColor'     => ['rgba(34, 197, 94, 1)', 'rgba(239, 68, 68, 1)'],
          'borderWidth'     => 2,
        ],
      ],
      'labels' => ['Healthy', 'Unhealthy'],
    ];
  }

  protected function getType(): string
  {
    return 'doughnut';
  }
}
