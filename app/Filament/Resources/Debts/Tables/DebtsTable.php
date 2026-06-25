<?php

namespace App\Filament\Resources\Debts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class DebtsTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('code')
          ->label('Code')
          ->searchable()
          ->sortable()
          ->copyable()
          ->badge()
          ->color('gray'),
        TextColumn::make('platform_name')
          ->label('Platform')
          ->searchable()
          ->sortable(),
        TextColumn::make('name')
          ->label('Name')
          ->searchable()
          ->sortable(),
        TextColumn::make('principal_amount')
          ->label('Principal')
          ->sortable()
          ->formatStateUsing(fn($state) => toIndonesianCurrency($state)),
        TextColumn::make('admin_fee')
          ->label('Admin Fee')
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true)
          ->formatStateUsing(fn($state) => toIndonesianCurrency($state)),
        TextColumn::make('disbursement_amount')
          ->label('Net Disbursement')
          ->sortable()
          ->formatStateUsing(fn($state) => toIndonesianCurrency($state)),
        TextColumn::make('interest_rate')
          ->label('Interest Rate')
          ->sortable()
          ->formatStateUsing(fn($state) => number_format($state, 2) . '%'),
        TextColumn::make('service_fee_rate')
          ->label('Service Fee Rate')
          ->sortable()
          ->formatStateUsing(fn($state) => number_format($state, 2) . '%'),
        TextColumn::make('tenor')
          ->label('Tenor')
          ->sortable()
          ->formatStateUsing(fn($state) => $state . ' Months'),
        TextColumn::make('start_date')
          ->label('Start Date')
          ->date('M d, Y')
          ->sortable(),
        TextColumn::make('status')
          ->label('Status')
          ->badge()
          ->color(fn(string $state): string => match ($state) {
            'paid' => 'success',
            'ongoing' => 'warning',
            default => 'primary'
          }),
      ])
      ->filters([
        TrashedFilter::make(),
      ])
      ->recordActions([
        ViewAction::make(),
        EditAction::make(),
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
