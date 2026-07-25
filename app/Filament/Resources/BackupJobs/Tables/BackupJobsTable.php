<?php

namespace App\Filament\Resources\BackupJobs\Tables;

use App\Enums\BackupJobStatus;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class BackupJobsTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('index')
          ->rowIndex()
          ->label('#'),
        TextColumn::make('backupSchedule.name')
          ->label('Backup Schedule')
          ->searchable()
          ->sortable()
          ->toggleable(),
        TextColumn::make('status')
          ->label('Status')
          ->badge()
          ->searchable()
          ->sortable()
          ->toggleable(),
        TextColumn::make('assigned_at')
          ->label('Assigned At')
          ->dateTime('M d, Y H:i')
          ->sortable()
          ->toggleable(),
        TextColumn::make('started_at')
          ->label('Started At')
          ->dateTime('M d, Y H:i')
          ->sortable()
          ->toggleable(),
        TextColumn::make('finished_at')
          ->label('Finished At')
          ->dateTime('M d, Y H:i')
          ->sortable()
          ->toggleable(),
        TextColumn::make('message')
          ->label('Message')
          ->limit(30)
          ->searchable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('created_at')
          ->label('Created At')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('updated_at')
          ->label('Updated At')
          ->dateTime()
          ->sortable()
          ->sinceTooltip()
          ->toggleable(isToggledHiddenByDefault: false),
        TextColumn::make('deleted_at')
          ->label('Deleted At')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->filters([
        SelectFilter::make('backup_schedule_id')
          ->label('Backup Schedule')
          ->relationship('backupSchedule', 'name')
          ->native(false),
        SelectFilter::make('status')
          ->label('Status')
          ->options(BackupJobStatus::class)
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
