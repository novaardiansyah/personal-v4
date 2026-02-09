<?php

/*
 * Project Name: personal-v4
 * File: UptimeMonitorLogsTable.php
 * Created Date: Sunday February 8th 2026
 *
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 *
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

declare(strict_types=1);

namespace App\Filament\Resources\UptimeMonitorLogs\Tables;

use App\Models\HttpStatus;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class UptimeMonitorLogsTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('index')
          ->label('#')
          ->rowIndex(),
        TextColumn::make('uptimeMonitor.name')
          ->label('Monitor')
          ->searchable()
          ->limit(30)
          ->tooltip(fn(?string $state): ?string => $state)
          ->toggleable(),
        TextColumn::make('status_code')
          ->label('Status Code')
          ->badge()
          ->color(fn(?int $state): string => match (true) {
            $state >= 200 && $state < 300 => 'success',
            $state >= 300 && $state < 400 => 'warning',
            $state >= 400 && $state < 500 => 'danger',
            $state >= 500                 => 'danger',
            default                       => 'gray',
          })
          ->sortable()
          ->toggleable(),
        TextColumn::make('response_time_ms')
          ->label('Response Time')
          ->badge()
          ->color('primary')
          ->suffix(' ms')
          ->sortable()
          ->toggleable(),
        IconColumn::make('is_healthy')
          ->label('Healthy')
          ->boolean()
          ->toggleable(),
        TextColumn::make('checked_at')
          ->label('Checked At')
          ->dateTime()
          ->sortable()
          ->sinceTooltip()
          ->toggleable(),
        TextColumn::make('error_message')
          ->label('Error')
          ->limit(30)
          ->tooltip(fn(?string $state): ?string => $state)
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('created_at')
          ->dateTime()
          ->sortable()
          ->sinceTooltip()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('updated_at')
          ->dateTime()
          ->sortable()
          ->sinceTooltip()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('deleted_at')
          ->dateTime()
          ->sortable()
          ->sinceTooltip()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->filters([
        SelectFilter::make('status_code')
          ->label('Status Code')
          ->options(fn() => HttpStatus::all()->pluck('label', 'name')->toArray())
          ->native(false)
          ->preload()
          ->searchable(),
        TrashedFilter::make()
          ->native(false)
          ->preload()
          ->searchable(),
      ])
      ->recordUrl(null)
      ->recordAction(null)
      ->defaultSort('id', 'desc')
      ->recordActions([
        ActionGroup::make([
          ViewAction::make()
            ->modalHeading('Detail Monitor Log')
            ->slideOver(),
        ]),
      ])
      ->toolbarActions([
        BulkActionGroup::make([]),
      ]);
  }
}
