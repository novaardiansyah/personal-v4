<?php

/*
 * Project Name: personal-v4
 * File: PaymentDetails.php
 * Created Date: Sunday March 15th 2026
 * 
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 * 
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\Setting;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Attributes\Url;

class PaymentDetails extends Page implements HasTable
{
  use InteractsWithTable;

  protected static string $resource = PaymentResource::class;

  protected string $view = 'filament.resources.payments.pages.payment-details';

  protected static ?string $title = 'Payment Details';

  #[Url]
  public string $ids = '';

  public array $recordIds = [];

  public function mount(): void
  {
    $this->recordIds = array_filter(explode(',', $this->ids));
  }

  public function getTitle(): string
  {
    return 'Payment Details';
  }

  public function table(Table $table): Table
  {
    return $table
      ->query(
        Payment::query()->whereIn('id', $this->recordIds)
      )
      ->columns([
        TextColumn::make('index')
          ->rowIndex()
          ->label('#'),
        TextColumn::make('code')
          ->label('Transaction ID')
          ->searchable()
          ->copyable()
          ->badge(),
        TextColumn::make('amount')
          ->label('Nominal')
          ->formatStateUsing(fn(?string $state): string => toIndonesianCurrency($state ?? 0, showCurrency: Setting::showPaymentCurrency())),
        TextColumn::make('name')
          ->label('Notes')
          ->wrap()
          ->words(100)
          ->searchable(),
        TextColumn::make('payment_account.name')
          ->label('Payment'),
        TextColumn::make('payment_account_to.name')
          ->label('Payment To')
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('category.name')
          ->label('Category')
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('type_id')
          ->label('Type')
          ->badge()
          ->color(fn(string $state): string => match ((int) $state) {
            PaymentType::INCOME     => 'success',
            PaymentType::EXPENSE    => 'danger',
            PaymentType::TRANSFER   => 'info',
            PaymentType::WITHDRAWAL => 'warning',
            default                 => 'primary',
          })
          ->formatStateUsing(fn(Payment $record): string => $record->payment_type->name),
        IconColumn::make('is_scheduled')
          ->label('Scheduled')
          ->boolean()
          ->toggleable(isToggledHiddenByDefault: true),
        IconColumn::make('is_draft')
          ->label('Draft')
          ->boolean()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('date')
          ->label('Date')
          ->date('M d, Y')
          ->sortable(),
      ])
      ->defaultSort('date', 'desc');
  }
}
