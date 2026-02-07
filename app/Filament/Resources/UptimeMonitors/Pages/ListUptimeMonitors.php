<?php

namespace App\Filament\Resources\UptimeMonitors\Pages;

use App\Filament\Resources\UptimeMonitors\UptimeMonitorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUptimeMonitors extends ListRecords
{
    protected static string $resource = UptimeMonitorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
