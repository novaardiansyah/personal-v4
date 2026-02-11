<?php

namespace App\Filament\Resources\UptimeMonitors\Widgets;

use App\Models\UptimeMonitor;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ResponseTimeChart extends ChartWidget
{
  public ?Model $record = null;

  protected static bool $isDiscovered = false;

  protected ?string $heading = 'Response Time (ms)';

  protected ?string $maxHeight = '300px';

  protected int | string | array $columnSpan = 'full';

  protected function getData(): array
  {
    $record = $this->record;

    if (! $record instanceof UptimeMonitor) {
      return ['datasets' => [], 'labels' => []];
    }

    $logs = $record->logs()
      ->where('checked_at', '>=', now()->subDay())
      ->orderBy('checked_at', 'desc')
      ->limit(50)
      ->get()
      ->reverse()
      ->values();

    $labels = $logs->map(fn($log) => Carbon::parse($log->checked_at)->format('M j, H:i'));

    $responseTimes = $logs->pluck('response_time_ms');

    $colors = $logs->map(function ($log) {
      if (! $log->is_healthy) return 'rgba(239, 68, 68, 1)';
      if ($log->response_time_ms > 2000) return 'rgba(234, 179, 8, 1)';
      return 'rgba(34, 197, 94, 1)';
    });

    return [
      'datasets' => [
        [
          'label'           => 'Response Time (ms)',
          'data'            => $responseTimes->toArray(),
          'borderColor'     => 'rgba(59, 130, 246, 1)',
          'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
          'pointBackgroundColor' => $colors->toArray(),
          'pointBorderColor' => $colors->toArray(),
          'tension'         => 0.3,
          'fill'            => true,
          'pointRadius'     => 3,
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
