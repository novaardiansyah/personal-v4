<?php

namespace App\Filament\Resources\UptimeMonitors\Schemas;

use App\Models\UptimeMonitor;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UptimeMonitorInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('url'),
                TextEntry::make('name')
                    ->placeholder('-'),
                TextEntry::make('interval')
                    ->numeric(),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('last_checked_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('last_healthy_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('last_unhealthy_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('total_checks')
                    ->numeric(),
                TextEntry::make('healthy_checks')
                    ->numeric(),
                TextEntry::make('unhealthy_checks')
                    ->numeric(),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (UptimeMonitor $record): bool => $record->trashed()),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
