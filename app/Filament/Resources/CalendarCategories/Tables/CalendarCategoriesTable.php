<?php

namespace App\Filament\Resources\CalendarCategories\Tables;

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
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CalendarCategoriesTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('index')
          ->rowIndex()
          ->label('#'),
        TextColumn::make('name')
          ->searchable()
          ->sortable(),
        TextColumn::make('color')
          ->label('Color')
          ->formatStateUsing(
            fn(string $state): string =>
            "<span style='display: inline-flex; align-items: center; gap: 0.5rem;'>
              <span style='width: 1rem; height: 1rem; border-radius: 50%; background-color: {$state}; border: 1px solid #e5e7eb;'></span>
              <span>{$state}</span>
            </span>"
          )
          ->html()
          ->copyable(),
        IconColumn::make('is_default')
          ->boolean()
          ->label('Default'),
        TextColumn::make('events_count')
          ->label('Event count')
          ->counts('events')
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
          ->sinceTooltip()
          ->toggleable(isToggledHiddenByDefault: false),
      ])
      ->filters([
        TrashedFilter::make()
          ->native(false),
      ])
      ->defaultSort('name', 'asc')
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
