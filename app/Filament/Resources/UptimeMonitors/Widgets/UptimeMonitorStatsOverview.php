<?php

namespace App\Filament\Resources\UptimeMonitors\Widgets;

use App\Models\UptimeMonitor;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class UptimeMonitorStatsOverview extends StatsOverviewWidget
{
  public ?Model $record = null;

  protected static bool $isDiscovered = false;

  protected function getStats(): array
  {
    $record = $this->record;

    if (! $record instanceof UptimeMonitor) {
      return [];
    }

    $totalChecks = $record->total_checks ?? 0;
    $healthyChecks = $record->healthy_checks ?? 0;
    $unhealthyChecks = $record->unhealthy_checks ?? 0;
    $uptimePercentage = $totalChecks > 0 ? round(($healthyChecks / $totalChecks) * 100, 2) : 0;

    $avgResponseTime = $record->logs()->avg('response_time_ms');
    $avgResponseTime = $avgResponseTime ? round($avgResponseTime, 2) : 0;

    $lastLog = $record->logs()->latest('checked_at')->first();
    $lastResponseTime = $lastLog?->response_time_ms ?? 0;

    $minResponseTime = $record->logs()->min('response_time_ms') ?? 0;
    $maxResponseTime = $record->logs()->max('response_time_ms') ?? 0;

    return [
      Stat::make('Current Status', $record->status?->getLabel() ?? '-')
        ->icon('heroicon-o-signal')
        ->color($record->status?->getColor() ?? 'gray'),
      Stat::make('Uptime', $uptimePercentage . '%')
        ->description($healthyChecks . ' healthy of ' . $totalChecks . ' checks')
        ->icon('heroicon-o-arrow-trending-up')
        ->color($uptimePercentage >= 99 ? 'success' : ($uptimePercentage >= 95 ? 'warning' : 'danger')),
      Stat::make('Avg Response Time', $avgResponseTime . ' ms')
        ->description('Min: ' . $minResponseTime . ' ms / Max: ' . $maxResponseTime . ' ms')
        ->icon('heroicon-o-clock')
        ->color('primary'),
      Stat::make('Last Response Time', $lastResponseTime . ' ms')
        ->description('Checked: ' . ($lastLog?->checked_at?->diffForHumans() ?? '-'))
        ->icon('heroicon-o-bolt')
        ->color('info'),
      Stat::make('Healthy Checks', (string) $healthyChecks)
        ->icon('heroicon-o-check-circle')
        ->color('success'),
      Stat::make('Unhealthy Checks', (string) $unhealthyChecks)
        ->icon('heroicon-o-x-circle')
        ->color('danger'),
    ];
  }
}
