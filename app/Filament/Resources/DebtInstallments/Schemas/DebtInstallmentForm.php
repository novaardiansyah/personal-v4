<?php

/*
 * Project Name: personal-v4
 * File: DebtInstallmentForm.php
 * Created Date: Thursday June 25th 2026
 * 
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 * 
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

namespace App\Filament\Resources\DebtInstallments\Schemas;

use Filament\Forms\Components\DatePicker;
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
          ->searchable()
          ->disabled(),
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
          ->required()
          ->disabled(),
      ]);
  }
}
