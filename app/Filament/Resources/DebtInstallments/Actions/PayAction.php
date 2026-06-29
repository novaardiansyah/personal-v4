<?php

/*
 * Project Name: personal-v4
 * File: PayAction.php
 * Created Date: Monday June 29th 2026
 * 
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 * 
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

namespace App\Filament\Resources\DebtInstallments\Actions;

use App\Models\DebtInstallment;
use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Models\PaymentType;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;

class PayAction
{
  public static function make(): Action
  {
    return Action::make('pay')
      ->label('Payment')
      ->icon('heroicon-o-credit-card')
      ->color('success')
      ->visible(fn(DebtInstallment $record): bool => $record->status === 'unpaid')
      ->modalHeading('Debt Payment Process')
      ->modalWidth(Width::Medium)
      ->form([
        Select::make('payment_account_id')
          ->label('Payment Account')
          ->options(PaymentAccount::where('user_id', auth()->id())->orderBy('name', 'asc')->pluck('name', 'id'))
          ->required()
          ->native(false)
          ->live()
          ->hint(fn($state) => $state ? toIndonesianCurrency(PaymentAccount::find($state)?->deposit ?? 0) : null),
        TextInput::make('total_due')	
          ->label('Total Due')
          ->disabled()
          ->dehydrated(false)
          ->default(fn(DebtInstallment $record) => toIndonesianCurrency($record->total_amount)),
        TextInput::make('remaining_balance')
          ->label('Remaining Balance')
          ->disabled()
          ->dehydrated(false)
          ->placeholder(function ($get, DebtInstallment $record) {
            $accountId = $get('payment_account_id');
            if (!$accountId) {
              return null;
            }
            $deposit = PaymentAccount::find($accountId)?->deposit ?? 0;
            return toIndonesianCurrency($deposit - $record->total_amount);
          }),
      ])
      ->action(function (DebtInstallment $record, array $data): void {
        try {
          $payment = Payment::create([
            'type_id'            => PaymentType::EXPENSE,
            'user_id'            => auth()->id(),
            'payment_account_id' => $data['payment_account_id'],
            'name'               => 'Bayar Cicilan ke-' . $record->installment_number . ': ' . $record->debt->platform_name . ' - ' . $record->debt->name,
            'amount'             => $record->total_amount,
            'date'               => Carbon::now(),
            'is_draft'           => false,
            'is_scheduled'       => false,
          ]);

          $record->update([
            'status'     => 'paid',
            'paid_at'    => Carbon::now(),
            'payment_id' => $payment->id,
          ]);

          Notification::make()
            ->success()
            ->title('Payment Recorded')
            ->body('The installment payment has been successfully recorded.')
            ->send();
        } catch (\Exception $e) {
          Notification::make()
            ->danger()
            ->title('Payment Failed')
            ->body($e->getMessage())
            ->send();
        }
      });
  }
}
