<?php

namespace App\Filament\Resources\UptimeMonitors\Actions;

use App\Filament\Resources\UptimeMonitors\Pages\StatisticsUptimeMonitor;
use App\Filament\Resources\UptimeMonitors\UptimeMonitorResource;
use App\Models\UptimeMonitor;
use Filament\Actions\Action;

class UptimeMonitorActions
{
  public static function log()
  {
    return Action::make('log')
      ->label('Uptime Logs')
      ->color('success')
      ->icon('heroicon-o-document-text')
      ->url(fn(UptimeMonitor $record): string => route('filament.admin.resources.uptime-monitor-logs.index', [
        'filters' => [
          'uptime_monitor_id' => [
            'value' => $record->id,
          ],
        ],
      ]), true);
  }

  public static function statistics()
  {
    return Action::make('statistics')
      ->label('Statistics')
      ->color('info')
      ->icon('heroicon-o-chart-bar')
      ->url(fn(UptimeMonitor $record): string => UptimeMonitorResource::getUrl('statistics', ['record' => $record]));
  }
}
