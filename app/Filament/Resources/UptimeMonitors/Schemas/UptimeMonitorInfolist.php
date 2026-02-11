<?php

/*
 * Project Name: personal-v4
 * File: UptimeMonitorInfolist.php
 * Created Date: Saturday February 8th 2026
 *
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 *
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

declare(strict_types=1);

namespace App\Filament\Resources\UptimeMonitors\Schemas;

use App\Models\UptimeMonitor;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UptimeMonitorInfolist
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make([
          TextEntry::make('code')
            ->label('Uptime ID')
            ->badge(),

          TextEntry::make('name')
            ->placeholder('-'),

          TextEntry::make('interval')
            ->formatStateUsing(fn(?string $state): string => secondsToHumanReadable((int) $state))
            ->badge()
            ->color('warning'),

          TextEntry::make('url')
            ->copyable()
            ->columnSpanFull(),
        ])
          ->description('General information')
          ->collapsible()
          ->columns(3),

        Section::make([
          IconEntry::make('is_active')
            ->boolean(),

          TextEntry::make('last_checked_at')
            ->label('Last Checked')
            ->dateTime()
            ->sinceTooltip()
            ->placeholder('-'),

          TextEntry::make('next_check_at')
            ->label('Next Check')
            ->dateTime()
            ->sinceTooltip()
            ->placeholder('-'),

          TextEntry::make('last_healthy_at')
            ->label('Last Healthy')
            ->dateTime()
            ->sinceTooltip()
            ->placeholder('-'),

          TextEntry::make('last_unhealthy_at')
            ->label('Last Unhealthy')
            ->dateTime()
            ->sinceTooltip()
            ->placeholder('-'),
        ])
          ->description('Check timestamps')
          ->collapsible()
          ->columns(3),

        Section::make([
          TextEntry::make('status')
            ->label('Status')
            ->badge()
            ->color(fn(UptimeMonitor $record): string => $record->status->getColor())
            ->formatStateUsing(fn(UptimeMonitor $record): string => $record->status->getLabel()),

          TextEntry::make('total_checks')
            ->label('Total Checks')
            ->badge()
            ->color('primary'),

          TextEntry::make('healthy_checks')
            ->label('Healthy Checks')
            ->badge()
            ->color('success'),

          TextEntry::make('unhealthy_checks')
            ->label('Unhealthy Checks')
            ->badge()
            ->color('danger'),
        ])
          ->description('Statistics')
          ->collapsible()
          ->columns(4),

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
