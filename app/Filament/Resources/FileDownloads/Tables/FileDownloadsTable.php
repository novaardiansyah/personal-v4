<?php

namespace App\Filament\Resources\FileDownloads\Tables;

use App\Models\FileDownload;
use Filament\Actions\Action;
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

class FileDownloadsTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('index')
          ->label('#')
          ->rowIndex(),
        TextColumn::make('code')
          ->label('File ID')
          ->searchable()
          ->badge()
          ->copyable()
          ->toggleable(),
        TextColumn::make('uid')
          ->label('UID')
          ->searchable()
          ->copyable()
          ->toggleable(),
        TextColumn::make('download_url')
          ->label('Download URL')
          ->copyable()
          ->wrap()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('status')
          ->badge()
          ->formatStateUsing(fn(FileDownload $record) => $record->status->label())
          ->color(fn(FileDownload $record) => $record->status->color())
          ->toggleable(),
        TextColumn::make('download_count')
          ->label('Download')
          ->numeric()
          ->sortable()
          ->badge()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('access_count')
          ->label('Access')
          ->numeric()
          ->sortable()
          ->badge()
          ->toggleable(),
        TextColumn::make('files_count')
          ->label('Files')
          ->counts('files')
          ->sortable()
          ->badge()
          ->toggleable(),
        TextColumn::make('deleted_at')
          ->dateTime()
          ->sortable()
          ->sinceTooltip()
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
          ->toggleable(),
      ])
      ->filters([
        TrashedFilter::make()
          ->native(false),
      ])
      ->defaultSort('updated_at', 'desc')
      ->recordActions([
        ActionGroup::make([
          ViewAction::make(),
          EditAction::make(),
          DeleteAction::make(),
          ForceDeleteAction::make(),
          RestoreAction::make(),
        ])
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
