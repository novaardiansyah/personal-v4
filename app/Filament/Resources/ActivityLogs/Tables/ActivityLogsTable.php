<?php

namespace App\Filament\Resources\ActivityLogs\Tables;

use App\Models\ActivityLog;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ActivityLogsTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('#')
          ->label('#')
          ->rowIndex(),
        TextColumn::make('log_name')
          ->label('Group')
          ->badge()
          ->color(fn($state) => ActivityLog::getLognameColor($state))
          ->formatStateUsing(fn($state) => ucwords($state))
          ->toggleable(),
        TextColumn::make('event')
          ->label('Event')
          ->badge()
          ->color(fn($state) => ActivityLog::getEventColor($state))
          ->toggleable(),
        TextColumn::make('description')
          ->label('Description')
          ->toggleable()
          ->wrap()
          ->limit(80)
          ->searchable(),
        TextColumn::make('subject_id')
          ->label('Subject')
          ->formatStateUsing(function ($state, ActivityLog $record) {
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
      ->filters([
        // TrashedFilter::make(),
      ])
      ->defaultSort('updated_at', 'desc')
      ->recordUrl(null)
      ->recordActions([
        ActionGroup::make([
          ViewAction::make()
            ->modalHeading('View detail activity log')
            ->slideOver()
            ->modalWidth(Width::FiveExtraLarge),

          Action::make('preview_email')
            ->modalHeading('Preview mail notification')
            ->color('info')
            ->icon('heroicon-o-envelope')
            ->url(function (ActivityLog $record): string {
              return url('admin/activity-logs/' . $record->id . '/preview-email');
            })
            ->openUrlInNewTab()
            ->visible(fn(ActivityLog $record): bool => $record->event === 'Mail Notification'),
        ])
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          // ! Not used
        ]),
      ]);
  }
}
