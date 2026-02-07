<?php

/*
 * Project Name: personal-v4
 * File: UptimeMonitorsTable.php
 * Created Date: Saturday February 7th 2026
 *
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 *
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

declare(strict_types=1);

namespace App\Filament\Resources\UptimeMonitors\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class UptimeMonitorsTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('index')
          ->label('#')
          ->rowIndex(),
        TextColumn::make('code')
          ->label('Uptime ID')
          ->searchable()
          ->copyable()
          ->toggleable()
          ->sortable()
          ->badge(),
        TextColumn::make('name')
          ->label('Name')
          ->searchable()
          ->limit(50)
          ->toggleable(),
        TextColumn::make('url')
          ->label('URL')
          ->searchable()
          ->limit(25)
          ->tooltip(fn(?string $state): ?string => $state)
          ->copyable()
          ->toggleable(),
        TextColumn::make('interval')
          ->label('Interval')
          ->badge()
          ->color('warning')
          ->sortable()
          ->toggleable()
          ->formatStateUsing(fn(?string $state): string => secondsToHumanReadable((int) $state)),
        TextColumn::make('last_checked_at')
          ->label('Last Checked')
          ->dateTime()
          ->sortable()
          ->sinceTooltip()
          ->toggleable(),
        TextColumn::make('next_check_at')
          ->label('Next Check')
          ->dateTime()
          ->sortable()
          ->sinceTooltip()
          ->toggleable(),
        TextColumn::make('last_healthy_at')
          ->label('Last Healthy')
          ->dateTime()
          ->sortable()
          ->sinceTooltip()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('last_unhealthy_at')
          ->label('Last Unhealthy')
          ->dateTime()
          ->sortable()
          ->sinceTooltip()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('total_checks')
          ->label('Total Checks')
          ->badge()
          ->color('primary')
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('healthy_checks')
          ->label('Healthy Checks')
          ->badge()
          ->color('success')
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('unhealthy_checks')
          ->label('Unhealthy Checks')
          ->badge()
          ->color('danger')
          ->sortable()
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
        TrashedFilter::make()
          ->native(false)
          ->preload()
          ->searchable(),
      ])
      ->recordUrl(null)
      ->recordActions([
        ActionGroup::make([
          ViewAction::make(),
          EditAction::make(),
          DeleteAction::make(),
          ForceDeleteAction::make(),
          RestoreAction::make(),
        ]),
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          DeleteBulkAction::make(),
          ForceDeleteBulkAction::make(),
          RestoreBulkAction::make(),
        ]),
      ]);
  }
}
