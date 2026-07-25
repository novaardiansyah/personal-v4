<?php

namespace App\Filament\Resources\BackupSchedules\Tables;

use App\Enums\BackupScheduleIntervalUnit;
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
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class BackupSchedulesTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('index')
          ->rowIndex()
          ->label('#'),
        TextColumn::make('uid')
          ->label('UID')
          ->searchable()
          ->copyable()
          ->badge()
          ->toggleable(),
        TextColumn::make('source_path')
          ->label('Source Path')
          ->searchable()
          ->limit(35)
          ->toggleable(),
        TextColumn::make('destination_path')
          ->label('Destination Path')
          ->searchable()
          ->limit(25)
          ->toggleable(),
        TextColumn::make('interval')
          ->label('Interval')
          ->getStateUsing(fn($record) => "Every {$record->interval_value} {$record->interval_unit?->getLabel()}")
          ->badge()
          ->color('info')
          ->toggleable(),
        TextColumn::make('retention_days')
          ->label('Retention')
          ->formatStateUsing(fn($state) => "{$state} days")
          ->sortable()
          ->toggleable(),
        IconColumn::make('is_enabled')
          ->label('Enabled')
          ->boolean()
          ->toggleable(),
        TextColumn::make('next_backup_at')
          ->label('Next Backup At')
          ->dateTime('M d, Y H:i')
          ->sortable()
          ->toggleable(),
        TextColumn::make('last_backup_at')
          ->label('Last Backup At')
          ->dateTime('M d, Y H:i')
          ->sortable()
          ->toggleable(),
        TextColumn::make('created_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('updated_at')
          ->dateTime()
          ->sortable()
          ->sinceTooltip()
          ->toggleable(isToggledHiddenByDefault: false),
        TextColumn::make('deleted_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->filters([
        SelectFilter::make('interval_unit')
          ->label('Interval Unit')
          ->options(BackupScheduleIntervalUnit::class)
          ->native(false),
        TrashedFilter::make()
          ->native(false),
      ])
      ->defaultSort('created_at', 'desc')
      ->recordAction(null)
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
