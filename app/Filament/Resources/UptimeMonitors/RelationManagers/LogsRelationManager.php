<?php

namespace App\Filament\Resources\UptimeMonitors\RelationManagers;

use App\Filament\Resources\UptimeMonitorLogs\UptimeMonitorLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class LogsRelationManager extends RelationManager
{
    protected static string $relationship = 'logs';

    protected static ?string $relatedResource = UptimeMonitorLogResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
