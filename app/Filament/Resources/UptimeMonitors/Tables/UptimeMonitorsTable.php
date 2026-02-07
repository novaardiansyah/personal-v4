<?php

namespace App\Filament\Resources\UptimeMonitors\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class UptimeMonitorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('url')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('interval')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('last_checked_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('last_healthy_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('last_unhealthy_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('total_checks')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('healthy_checks')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('unhealthy_checks')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
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
