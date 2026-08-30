<?php

namespace App\Filament\Resources\DiscordServers\Tables;

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
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class DiscordServersTable
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
          ->color('info')
          ->limit(8)
          ->tooltip(fn($state) => $state . ' (click to copy)')
          ->toggleable(),
        TextColumn::make('name')
          ->label('Name')
          ->searchable()
          ->sortable()
          ->toggleable(),
        ImageColumn::make('server_icon')
          ->label('Server Icon')
          ->checkFileExistence(false)
          ->circular()
          ->imageWidth(30)
          ->imageHeight(30)
          ->toggleable(),
        TextColumn::make('server_id')
          ->label('Server ID')
          ->searchable()
          ->sortable()
          ->copyable()
          ->badge()
          ->color('info')
          ->toggleable(),
        IconColumn::make('is_active')
          ->label('Active')
          ->boolean()
          ->sortable()
          ->toggleable(),
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
        SelectFilter::make('is_active')
          ->label('Active')
          ->options([
            1 => 'Active',
            0 => 'Inactive',
          ])
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
