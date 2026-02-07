<?php

namespace App\Filament\Resources\UptimeMonitors\Pages;

use App\Filament\Resources\UptimeMonitors\UptimeMonitorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUptimeMonitor extends CreateRecord
{
  protected static string $resource = UptimeMonitorResource::class;

  protected function getRedirectUrl(): string
  {
    $resource = static::getResource();
    return $resource::getUrl('index');
  }
}
