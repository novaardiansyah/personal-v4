<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Str;

class ActivityLogTableWidget extends TableWidget
{
  protected static ?string $heading = 'Latest Activity Logs';
  protected int | string | array $columnSpan = 1;

  public function table(Table $table): Table
  {
    return $table
      ->query(
        ActivityLog::query()
            ->limit(10)
        )
      ->columns([
        TextColumn::make('#')
          ->label('#')
          ->rowIndex(),
        TextColumn::make('log_name')
          ->label('Group')
          ->badge()
          ->formatStateUsing(fn($state) => ucwords($state))
          ->toggleable(),
        TextColumn::make('event')
          ->label('Event')
          ->badge()
          ->color(fn ($state) => ActivityLog::getEventColor($state))
          ->toggleable(),
        TextColumn::make('description')
          ->label('Description')
          ->toggleable()
          ->wrap()
          ->limit(80)
          ->searchable(),
        TextColumn::make('subject_id')
          ->label('Subject')
          ->formatStateUsing(function ($state, Model $record) {
            if (!$state) return '-';
            return Str::of($record->subject_type)->afterLast('\\')->headline() . ' # ' . $state;
          })
          ->toggleable()
          ->searchable(),
        TextColumn::make('causer.name')
          ->label('Causer')
          ->searchable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('batch_uuid')
          ->searchable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('created_at')
          ->dateTime()
          ->sortable()
          ->sinceTooltip()
          ->toggleable(),
      ])
      ->paginated(false)
      ->filters([
        //
      ])
      ->defaultSort('updated_at', 'desc')
      ->headerActions([
        //
      ])
      ->recordActions([
        //
      ])
      ->recordUrl(function ($record) {
        return url("/admin/activity-logs?tableAction=view&tableActionRecord={$record->id}");
      }, true)
      ->toolbarActions([
        BulkActionGroup::make([
          //
        ]),
      ]);
  }
}
