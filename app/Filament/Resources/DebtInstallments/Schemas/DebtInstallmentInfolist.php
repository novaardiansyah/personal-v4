<?php

/*
 * Project Name: personal-v4
 * File: DebtInstallmentInfolist.php
 * Created Date: Thursday June 25th 2026
 * 
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 * 
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

namespace App\Filament\Resources\DebtInstallments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DebtInstallmentInfolist
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make('')
          ->description('Installment Information')
          ->schema([
            TextEntry::make('debt.name')
              ->label('Debt')
              ->placeholder('N/A'),
            TextEntry::make('payment.name')
              ->label('Payment')
              ->placeholder('-'),
            TextEntry::make('installment_number')
              ->label('Installment')
							->prefix('#')
              ->numeric(),
            TextEntry::make('due_date')
              ->label('Due Date')
              ->date('M d, Y'),
            TextEntry::make('status')
              ->label('Status')
              ->badge()
              ->color(fn(string $state): string => match ($state) {
                'paid'   => 'success',
                'unpaid' => 'warning',
                default  => 'primary'
              }),
            TextEntry::make('paid_at')
              ->label('Paid At')
              ->dateTime('M d, Y H:i')
              ->placeholder('-'),
            TextEntry::make('principal_amount')
              ->label('Principal')
              ->formatStateUsing(fn(?string $state) => toIndonesianCurrency((float) ($state ?? 0))),
            TextEntry::make('interest_amount')
              ->label('Interest')
              ->formatStateUsing(fn(?string $state) => toIndonesianCurrency((float) ($state ?? 0))),
            TextEntry::make('service_fee')
              ->label('Service Fee')
              ->formatStateUsing(fn(?string $state) => toIndonesianCurrency((float) ($state ?? 0))),
            TextEntry::make('vat_amount')
              ->label('VAT')
              ->formatStateUsing(fn(?string $state) => toIndonesianCurrency((float) ($state ?? 0))),
            TextEntry::make('penalty_amount')
              ->label('Penalty')
              ->formatStateUsing(fn(?string $state) => toIndonesianCurrency((float) ($state ?? 0))),
            TextEntry::make('total_amount')
              ->label('Total')
              ->formatStateUsing(fn(?string $state) => toIndonesianCurrency((float) ($state ?? 0))),
          ])
          ->columns(['xl' => 3, '2xl' => 4])
          ->columnSpan(['sm' => 3, 'md' => 2]),

        Section::make('')
          ->description('System Information')
          ->schema([
            TextEntry::make('created_at')
              ->label('Created At')
              ->dateTime()
							->sinceTooltip(),
            TextEntry::make('updated_at')
              ->label('Last Updated')
              ->dateTime()
              ->sinceTooltip(),
						TextEntry::make('deleted_at')
							->label('Deleted At')
							->dateTime()
							->sinceTooltip()
							->placeholder('N/A'),
          ])
          ->columns(1)
          ->columnSpan(['sm' => 3, 'md' => 1]),
      ])
      ->columns(3);
  }
}
