<?php

namespace App\Filament\Resources\Backups\Tables;

use App\Enums\BackupStatus;
use App\Enums\BackupType;
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

class BackupsTable
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
          ->badge()
          ->copyable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('file_name')
          ->label('File Name')
          ->searchable()
          ->limit(30)
          ->toggleable(),
        TextColumn::make('file_path')
          ->label('File Path')
          ->searchable()
          ->limit(35)
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('type')
          ->label('Type')
          ->badge()
          ->searchable()
          ->sortable()
          ->toggleable(),
        TextColumn::make('status')
          ->label('Status')
          ->badge()
          ->searchable()
          ->sortable()
          ->toggleable(),
        TextColumn::make('file_size')
          ->label('File Size')
          ->formatStateUsing(fn($state) => $state ? sizeFormat(floatval($state)) : 'N/A')
          ->sortable()
          ->toggleable(),
        TextColumn::make('duration')
          ->label('Duration')
          ->formatStateUsing(fn($state) => "{$state}s")
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('checksum')
          ->label('Checksum')
          ->searchable()
          ->limit(15)
          ->copyable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('server_name')
          ->label('Server Name')
          ->searchable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('started_at')
          ->label('Started At')
          ->dateTime('M d, Y H:i')
          ->sinceTooltip()
          ->sortable()
          ->toggleable(),
        TextColumn::make('completed_at')
          ->label('Completed At')
          ->dateTime('M d, Y H:i')
          ->sinceTooltip()
          ->sortable()
          ->toggleable(),
        TextColumn::make('created_at')
          ->label('Created At')
          ->dateTime()
          ->sinceTooltip()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('updated_at')
          ->label('Updated At')
          ->dateTime()
          ->sinceTooltip()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: false),
        TextColumn::make('deleted_at')
          ->label('Deleted At')
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
        SelectFilter::make('status')
          ->label('Status')
          ->options(BackupStatus::class)
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
