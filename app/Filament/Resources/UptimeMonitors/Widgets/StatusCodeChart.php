<?php

namespace App\Filament\Resources\UptimeMonitors\Widgets;

use App\Models\HttpStatus;
use App\Models\UptimeMonitor;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;

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

    $statusCodesData = $record->logs()
      ->where('checked_at', '>=', now()->subDays(7))
      ->selectRaw('status_code, COUNT(*) as count')
      ->groupBy('status_code')
      ->orderBy('status_code')
      ->get();

    $colors = $statusCodesData->map(function ($item) {
      $code = $item->status_code;
      return match (true) {
        $code >= 200 && $code < 300 => 'rgba(34, 197, 94, 0.8)',
        $code >= 300 && $code < 400 => 'rgba(234, 179, 8, 0.8)',
        $code >= 400 && $code < 500 => 'rgba(249, 115, 22, 0.8)',
        $code >= 500              => 'rgba(239, 68, 68, 0.8)',
        default                   => 'rgba(107, 114, 128, 0.8)',
      };
    });

    $labels = $statusCodesData->map(function ($item) {
      $status = HttpStatus::where('name', $item->status_code)->first();
      return $status ? $status->label : (string) $item->status_code;
    });

    $datasets = $statusCodesData->map(function ($item, $index) use ($colors, $labels) {
      return [
        'label'           => $labels[$index],
        'data'            => [$item->count],
        'backgroundColor' => $colors[$index],
        'borderColor'     => $colors[$index],
        'borderWidth'     => 1,
      ];
    })->toArray();

    return [
      'datasets' => $datasets,
      'labels'   => ['Status Distribution'],
    ];
  }

  protected function getType(): string
  {
    return 'bar';
  }
}
