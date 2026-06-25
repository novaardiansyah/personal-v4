<?php

namespace App\Filament\Resources\DebtInstallments\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DebtInstallmentForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Select::make('debt_id')
          ->relationship('debt', 'name')
          ->required()
          ->native(false)
          ->preload()
          ->searchable(),
        Select::make('payment_id')
          ->relationship('payment', 'name')
          ->nullable()
          ->native(false)
          ->preload()
          ->searchable(),
        TextInput::make('installment_number')
          ->required()
          ->numeric(),
        DatePicker::make('due_date')
          ->required()
          ->native(false),
        TextInput::make('principal_amount')
          ->required()
          ->numeric()
          ->default(0)
          ->hint(fn(?string $state) => toIndonesianCurrency($state ?? 0)),
        TextInput::make('interest_amount')
          ->required()
          ->numeric()
          ->default(0)
          ->hint(fn(?string $state) => toIndonesianCurrency($state ?? 0)),
        TextInput::make('service_fee')
          ->required()
          ->numeric()
          ->default(0)
          ->hint(fn(?string $state) => toIndonesianCurrency($state ?? 0)),
        TextInput::make('vat_amount')
          ->required()
          ->numeric()
          ->default(0)
          ->hint(fn(?string $state) => toIndonesianCurrency($state ?? 0)),
        TextInput::make('penalty_amount')
          ->required()
          ->numeric()
          ->default(0)
          ->hint(fn(?string $state) => toIndonesianCurrency($state ?? 0)),
        TextInput::make('total_amount')
          ->required()
          ->numeric()
          ->default(0)
          ->hint(fn(?string $state) => toIndonesianCurrency($state ?? 0)),
        Select::make('status')
          ->options([
            'unpaid' => 'Unpaid',
            'paid' => 'Paid',
          ])
          ->default('unpaid')
          ->required(),
        DateTimePicker::make('paid_at')
          ->native(false),
      ]);
  }
}
