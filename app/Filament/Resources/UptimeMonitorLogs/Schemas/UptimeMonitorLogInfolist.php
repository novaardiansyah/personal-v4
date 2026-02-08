<?php

/*
 * Project Name: personal-v4
 * File: UptimeMonitorLogInfolist.php
 * Created Date: Sunday February 8th 2026
 *
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 *
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

declare(strict_types=1);

namespace App\Filament\Resources\UptimeMonitorLogs\Schemas;

use App\Models\UptimeMonitorLog;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UptimeMonitorLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make([
                    TextEntry::make('uptimeMonitor.name')
                        ->label('Monitor')
                        ->placeholder('-'),

                    TextEntry::make('status_code')
                        ->label('Status Code')
                        ->badge()
                        ->color(fn(?int $state): string => match (true) {
                            $state >= 200 && $state < 300 => 'success',
                            $state >= 300 && $state < 400 => 'warning',
                            $state >= 400 && $state < 500 => 'danger',
                            $state >= 500                 => 'danger',
                            default                       => 'gray',
                        })
                        ->placeholder('-'),

                    TextEntry::make('response_time_ms')
                        ->label('Response Time')
                        ->badge()
                        ->color('primary')
                        ->suffix(' ms'),

                    IconEntry::make('is_healthy')
                        ->label('Healthy')
                        ->boolean(),

                    TextEntry::make('checked_at')
                        ->label('Checked At')
                        ->dateTime()
                        ->sinceTooltip()
                        ->placeholder('-'),
                ])
                    ->description('General information')
                    ->collapsible()
                    ->columns(3),

                Section::make([
                    TextEntry::make('error_message')
                        ->label('Error Message')
                        ->placeholder('-')
                        ->columnSpanFull(),
                ])
                    ->description('Error details')
                    ->collapsible()
                    ->columns(1),

                Section::make([
                    TextEntry::make('created_at')
                        ->dateTime()
                        ->sinceTooltip()
                        ->placeholder('-'),

                    TextEntry::make('updated_at')
                        ->dateTime()
                        ->sinceTooltip()
                        ->placeholder('-'),

                    TextEntry::make('deleted_at')
                        ->dateTime()
                        ->sinceTooltip()
                        ->placeholder('-'),
                ])
                    ->description('Timestamps')
                    ->collapsible()
                    ->columns(3),
            ])
            ->columns(1);
    }
}
