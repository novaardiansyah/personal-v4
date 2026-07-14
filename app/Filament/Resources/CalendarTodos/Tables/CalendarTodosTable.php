<?php

namespace App\Filament\Resources\CalendarTodos\Tables;

use App\Enums\TodoPriority;
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
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CalendarTodosTable
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
          ->badge(),
        TextColumn::make('title')
          ->searchable()
          ->sortable()
          ->wrap()
          ->limit(50),
        TextColumn::make('priority')
          ->badge()
          ->color(fn(TodoPriority $state): string => $state->color())
          ->formatStateUsing(fn(TodoPriority $state): string => $state->label())
          ->sortable(),
        TextColumn::make('due_at')
          ->dateTime('M d, Y H:i')
          ->sortable()
          ->sinceTooltip(),
        IconColumn::make('status')
          ->label('Status')
          ->boolean()
          ->getStateUsing(fn($record) => !is_null($record->completed_at)),
        TextColumn::make('event.title')
          ->label('Event')
          ->limit(30)
          ->toggleable()
          ->default(null),
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
          Action::make('toggle_complete')
            ->label(fn($record) => $record->completed_at ? 'Mark Incomplete' : 'Mark Complete')
            ->icon(fn($record) => $record->completed_at ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
            ->action(fn($record) => $record->update(['completed_at' => $record->completed_at ? null : now()])),
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
