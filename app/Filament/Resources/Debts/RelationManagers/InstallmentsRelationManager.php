<?php

namespace App\Filament\Resources\Debts\RelationManagers;

use App\Models\DebtInstallment;
use App\Models\PaymentAccount;
use App\Models\PaymentType;
use App\Models\Payment;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class InstallmentsRelationManager extends RelationManager
{
  protected static string $relationship = 'installments';

  public function table(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('installment_number')
          ->label('Installment #')
          ->sortable(),
        TextColumn::make('due_date')
          ->label('Due Date')
          ->date('M d, Y')
          ->sortable(),
        TextColumn::make('principal_amount')
          ->label('Principal')
          ->formatStateUsing(fn($state) => toIndonesianCurrency($state)),
        TextColumn::make('interest_amount')
          ->label('Interest')
          ->formatStateUsing(fn($state) => toIndonesianCurrency($state)),
        TextColumn::make('service_fee')
          ->label('Service Fee')
          ->formatStateUsing(fn($state) => toIndonesianCurrency($state)),
        TextColumn::make('vat_amount')
          ->label('VAT')
          ->formatStateUsing(fn($state) => toIndonesianCurrency($state)),
        TextColumn::make('total_amount')
          ->label('Total')
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
          ->toggleable(),
      ])
      ->defaultSort('installment_number', 'asc')
      ->actions([
        Action::make('pay')
          ->label('Pay')
          ->icon('heroicon-o-credit-card')
          ->color('success')
          ->visible(fn(DebtInstallment $record) => $record->status === 'unpaid')
          ->form([
            Select::make('payment_account_id')
              ->label('Source Account')
              ->options(PaymentAccount::where('user_id', auth()->id())->pluck('name', 'id'))
              ->required()
              ->native(false),
          ])
          ->action(function (DebtInstallment $record, array $data): void {
            $payment = Payment::create([
              'type_id' => PaymentType::EXPENSE,
              'user_id' => auth()->id(),
              'payment_account_id' => $data['payment_account_id'],
              'name' => 'Bayar Cicilan ke-' . $record->installment_number . ': ' . $record->debt->platform_name . ' - ' . $record->debt->name,
              'amount' => $record->total_amount,
              'date' => Carbon::now(),
              'is_draft' => false,
              'is_scheduled' => false,
            ]);

            $record->update([
              'status' => 'paid',
              'paid_at' => Carbon::now(),
              'payment_id' => $payment->id,
            ]);
          }),
      ]);
  }
}
