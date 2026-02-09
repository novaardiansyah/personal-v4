<?php

namespace App\Filament\Resources\UptimeMonitors\Actions;

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
}
