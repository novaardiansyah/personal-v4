<?php

namespace App\Filament\Resources\DebtInstallments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DebtInstallmentsTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('debt.name')
          ->label('Debt')
          ->searchable()
          ->sortable(),
        TextColumn::make('payment.name')
          ->label('Payment')
          ->searchable()
          ->sortable()
          ->placeholder('-'),
        TextColumn::make('installment_number')
          ->label('Installment #')
          ->numeric()
          ->sortable(),
        TextColumn::make('due_date')
          ->label('Due Date')
          ->date('M d, Y')
          ->sortable(),
        TextColumn::make('principal_amount')
          ->label('Principal')
          ->sortable()
          ->formatStateUsing(fn($state) => toIndonesianCurrency($state)),
        TextColumn::make('interest_amount')
          ->label('Interest')
          ->sortable()
          ->formatStateUsing(fn($state) => toIndonesianCurrency($state)),
        TextColumn::make('service_fee')
          ->label('Service Fee')
          ->sortable()
          ->formatStateUsing(fn($state) => toIndonesianCurrency($state)),
        TextColumn::make('vat_amount')
          ->label('VAT')
          ->sortable()
          ->formatStateUsing(fn($state) => toIndonesianCurrency($state)),
        TextColumn::make('penalty_amount')
          ->label('Penalty')
          ->sortable()
          ->formatStateUsing(fn($state) => toIndonesianCurrency($state)),
        TextColumn::make('total_amount')
          ->label('Total')
          ->sortable()
          ->formatStateUsing(fn($state) => toIndonesianCurrency($state)),
        TextColumn::make('status')
          ->label('Status')
          ->badge()
          ->color(fn(string $state): string => match ($state) {
            'paid' => 'success',
            'unpaid' => 'warning',
            default => 'primary'
          }),
        TextColumn::make('paid_at')
          ->label('Paid At')
          ->dateTime('M d, Y H:i')
          ->sortable(),
      ])
      ->filters([
        //
      ])
      ->recordActions([
        ViewAction::make(),
        EditAction::make(),
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          DeleteBulkAction::make(),
        ]),
      ]);
  }
}
