<?php

namespace App\Filament\Resources\UptimeMonitors\Pages;

use App\Filament\Resources\UptimeMonitors\UptimeMonitorResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUptimeMonitor extends ViewRecord
{
    protected static string $resource = UptimeMonitorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
