<?php

namespace App\Filament\Resources\UptimeMonitors\Widgets;

use App\Models\UptimeMonitor;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class StatusCodeChart extends ChartWidget
{
  public ?Model $record = null;

  protected static bool $isDiscovered = false;

  protected ?string $heading = 'Status Codes (Last 7 Days)';

  protected ?string $maxHeight = '300px';

  protected function getData(): array
  {
    $record = $this->record;

    if (! $record instanceof UptimeMonitor) {
      return ['datasets' => [], 'labels' => []];
    }

    $statusCodes = $record->logs()
      ->where('checked_at', '>=', now()->subDays(7))
      ->selectRaw('status_code, COUNT(*) as count')
      ->groupBy('status_code')
      ->orderBy('status_code')
      ->pluck('count', 'status_code');

    $colors = $statusCodes->keys()->map(function ($code) {
      return match (true) {
        $code >= 200 && $code < 300 => 'rgba(34, 197, 94, 0.8)',
        $code >= 300 && $code < 400 => 'rgba(234, 179, 8, 0.8)',
        $code >= 400 && $code < 500 => 'rgba(249, 115, 22, 0.8)',
        $code >= 500              => 'rgba(239, 68, 68, 0.8)',
        default                   => 'rgba(107, 114, 128, 0.8)',
      };
    });

    return [
      'datasets' => [
        [
          'data'            => $statusCodes->values()->toArray(),
          'backgroundColor' => $colors->toArray(),
          'borderWidth'     => 1,
        ],
      ],
      'labels' => $statusCodes->keys()->map(fn($code) => (string) $code)->toArray(),
    ];
  }

  protected function getType(): string
  {
    return 'bar';
  }
}
