<?php

namespace App\Filament\Resources\BackupSchedules\Tables;

use App\Enums\BackupScheduleIntervalUnit;
use App\Enums\BackupType;
use App\Filament\Resources\BackupSchedules\Actions\EnableDisableAction;
use App\Filament\Resources\BackupSchedules\Actions\ReplicateAction;
use App\Filament\Resources\BackupSchedules\Actions\SyncBackupAction;
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
          ->toggleable(isToggledHiddenByDefault: true),
				TextColumn::make('name')
					->label('Name')
					->searchable()
					->toggleable(),
				TextColumn::make('type')
					->label('Type')
					->badge()
					->searchable()
					->toggleable(),
				TextColumn::make('drivers')
					->label('Drivers')
					->searchable()
					->toggleable(isToggledHiddenByDefault: true),
				TextColumn::make('database_name')
					->label('Database Name')
					->searchable()
					->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('source_path')
          ->label('Source Path')
          ->searchable()
          ->limit(35)
          ->tooltip(fn($state) => $state)
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('exclude')
          ->label('Exclude')
          ->badge()
          ->separator(', ')
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('local_destination_path')
          ->label('Local Path')
          ->searchable()
          ->limit(35)
          ->tooltip(fn($state) => $state)
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('r2_destination_path')
          ->label('Cloud Destination Path')
          ->searchable()
          ->limit(35)
          ->tooltip(fn($state) => $state)
          ->toggleable(isToggledHiddenByDefault: true),
        IconColumn::make('keep_local_backup')
          ->label('Keep Local Backup')
          ->boolean()
          ->toggleable(isToggledHiddenByDefault: true),
        IconColumn::make('is_sync_cloud')
          ->label('Sync Cloud')
          ->boolean()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('interval')
          ->label('Interval')
          ->getStateUsing(fn($record) => "Every {$record->interval_value} {$record->interval_unit?->getLabel()}")
          ->badge()
          ->color('info')
          ->toggleable(),
        TextColumn::make('count_backup')
          ->label('Count')
          ->getStateUsing(fn($record) => "{$record->count_backup}/{$record->max_count_backup}")
          ->badge()
          ->sortable()
          ->toggleable(),
        TextColumn::make('sum_file_size')
          ->label('Total Size')
          ->formatStateUsing(fn($state) => sizeFormat(floatval($state)))
          ->sortable()
          ->toggleable(),
        IconColumn::make('is_enabled')
          ->label('Enabled')
          ->boolean()
          ->toggleable(),
        TextColumn::make('next_backup_at')
          ->label('Next Backup At')
          ->dateTime('M d, Y H:i')
					->sinceTooltip()
          ->sortable()
          ->toggleable(),
        TextColumn::make('last_backup_at')
          ->label('Last Backup At')
          ->dateTime('M d, Y H:i')
					->sinceTooltip()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('created_at')
          ->dateTime()
					->sinceTooltip()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('updated_at')
          ->dateTime()
          ->sinceTooltip()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: false),
        TextColumn::make('deleted_at')
          ->dateTime()
					->sinceTooltip()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->filters([
        SelectFilter::make('type')
          ->label('Type')
          ->options(BackupType::class)
          ->native(false),
        SelectFilter::make('interval_unit')
          ->label('Interval Unit')
          ->options(BackupScheduleIntervalUnit::class)
          ->native(false),
        TrashedFilter::make()
          ->native(false),
      ])
      ->defaultSort('next_backup_at', 'asc')
      ->recordAction(null)
      ->recordUrl(null)
      ->recordActions([
        ActionGroup::make([
          ViewAction::make(),
          EditAction::make(),
          SyncBackupAction::make(),
          EnableDisableAction::make(),
          ReplicateAction::make(),
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
