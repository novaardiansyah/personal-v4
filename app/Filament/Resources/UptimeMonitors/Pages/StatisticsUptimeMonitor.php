<?php

namespace App\Filament\Resources\UptimeMonitors\Pages;

use App\Filament\Resources\UptimeMonitors\UptimeMonitorResource;
use App\Filament\Resources\UptimeMonitors\Widgets\ResponseTimeChart;
use App\Filament\Resources\UptimeMonitors\Widgets\StatusCodeChart;
use App\Filament\Resources\UptimeMonitors\Widgets\UptimeMonitorStatsOverview;
use App\Filament\Resources\UptimeMonitors\Widgets\UptimeStatusChart;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class StatisticsUptimeMonitor extends Page
{
  use InteractsWithRecord;

  protected static string $resource = UptimeMonitorResource::class;

  protected string $view = 'filament.resources.uptime-monitors.pages.statistics-uptime-monitor';

  protected static ?string $title = 'Statistics';

  public function mount(int | string $record): void
  {
    $this->record = $this->resolveRecord($record);
  }

  public function getTitle(): string
  {
    return 'Statistics';
  }

  protected function getHeaderWidgets(): array
  {
    return [
      UptimeMonitorStatsOverview::class,
    ];
  }

  protected function getFooterWidgets(): array
  {
    return [
      ResponseTimeChart::class,
      UptimeStatusChart::class,
      StatusCodeChart::class,
    ];
  }

  public function getFooterWidgetsColumns(): int | array
  {
    return 2;
  }
}
