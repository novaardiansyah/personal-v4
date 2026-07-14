<?php

namespace App\Filament\Resources\CalendarEvents\Tables;

use App\Enums\RecurrenceType;
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

class CalendarEventsTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('index')
          ->rowIndex()
          ->label('#'),
        TextColumn::make('code')
          ->searchable()
          ->copyable()
          ->badge()
          ->toggleable(),
        TextColumn::make('title')
          ->searchable()
          ->sortable()
          ->wrap()
          ->limit(50)
          ->toggleable(),
        TextColumn::make('start_end')
          ->label('Start at → End at')
          ->getStateUsing(fn($record) => $record->start_at->format('M d, Y H:i') . ($record->end_at ? ' → ' . $record->end_at->format('M d, Y H:i') : ''))
          ->sortable(query: fn($query, $direction) => $query->orderBy('start_at', $direction))
          ->toggleable(),
        IconColumn::make('is_all_day')
          ->boolean()
          ->label('All day')
          ->toggleable(),
        TextColumn::make('category.name')
          ->label('Category')
          ->badge()
          ->color(fn($record) => $record->category?->color ?? 'gray')
          ->formatStateUsing(fn($record) => $record->category?->name ?? '-')
          ->sortable()
          ->toggleable(),
        TextColumn::make('recurrence_type')
          ->label('Recurrence')
          ->badge()
          ->formatStateUsing(fn(?string $state) => $state ? RecurrenceType::from($state)->label() : '-')
          ->color('info')
          ->toggleable(),
        TextColumn::make('source_link')
          ->label('Source')
          ->getStateUsing(fn($record) => $record->source_type ? $record->source_type . ' #' . $record->source_id : '-')
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
        SelectFilter::make('category_id')
          ->label('Category')
          ->relationship('category', 'name')
          ->native(false)
          ->preload()
          ->searchable(),
        TrashedFilter::make()
          ->native(false),
      ])
      ->defaultSort('start_at', 'desc')
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
