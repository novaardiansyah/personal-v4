<?php

namespace App\Filament\Resources\DiscordChannels\Tables;

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

class DiscordChannelsTable
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
          ->color('info')
          ->limit(8)
          ->copyable()
					->copyableState(fn($state) => $state)
          ->tooltip(fn($state) => $state)
          ->toggleable(),
        TextColumn::make('server.name')
          ->label('Server')
          ->badge()
          ->color('info')
          ->searchable()
          ->sortable()
          ->toggleable(),
        TextColumn::make('name')
          ->label('Name')
          ->searchable()
          ->sortable()
          ->toggleable(),
        TextColumn::make('channel_id')
          ->label('Channel ID')
          ->searchable()
          ->sortable()
          ->copyable()
          ->badge()
          ->color('info')
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
        SelectFilter::make('server_id')
          ->label('Server')
          ->relationship('server', 'name')
          ->searchable()
          ->preload()
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
