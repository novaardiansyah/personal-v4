<?php

namespace App\Filament\Resources\DebtInstallments\Tables;

use Filament\Actions\ActionGroup;
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
        TextColumn::make('index')
          ->rowIndex()
          ->label('#'),
        TextColumn::make('debt.name')
          ->label('Debt')
          ->searchable()
          ->sortable()
          ->toggleable(),
        TextColumn::make('payment.name')
          ->label('Payment')
          ->searchable()
          ->sortable()
          ->placeholder('-')
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('installment_number')
          ->label('Installment')
          ->numeric()
          ->sortable()
          ->toggleable()
					->prefix('#'),
        TextColumn::make('principal_amount')
          ->label('Principal')
          ->sortable()
          ->formatStateUsing(fn($state) => toIndonesianCurrency($state))
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('interest_amount')
          ->label('Interest')
          ->sortable()
          ->formatStateUsing(fn($state) => toIndonesianCurrency($state))
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('service_fee')
          ->label('Service Fee')
          ->sortable()
          ->formatStateUsing(fn($state) => toIndonesianCurrency($state))
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('vat_amount')
          ->label('VAT')
          ->sortable()
          ->formatStateUsing(fn($state) => toIndonesianCurrency($state))
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('penalty_amount')
          ->label('Penalty')
          ->sortable()
          ->formatStateUsing(fn($state) => toIndonesianCurrency($state))
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('total_amount')
          ->label('Total')
          ->sortable()
          ->formatStateUsing(fn($state) => toIndonesianCurrency($state))
          ->toggleable(),
        TextColumn::make('status')
          ->label('Status')
          ->badge()
          ->color(fn(string $state): string => match ($state) {
            'paid'   => 'success',
            'unpaid' => 'warning',
            default  => 'primary'
          }),
				TextColumn::make('due_date')
          ->label('Due Date')
          ->date('M d, Y')
          ->sortable()
          ->toggleable()
					->sinceTooltip(),
        TextColumn::make('paid_at')
          ->label('Paid At')
          ->dateTime('M d, Y H:i')
          ->sortable()
          ->toggleable(),
      ])
			->recordAction(null)
			->recordUrl(null)
      ->filters([
      ])
      ->recordActions([
        ActionGroup::make([
          ViewAction::make(),
          EditAction::make(),
        ])
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          DeleteBulkAction::make(),
        ]),
      ]);
  }
}
