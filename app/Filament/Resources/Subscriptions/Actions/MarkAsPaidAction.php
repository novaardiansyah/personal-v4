<?php

namespace App\Filament\Resources\Subscriptions\Actions;

use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Models\PaymentType;
use App\Models\Subscription;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

class MarkAsPaidAction
{
  public static function make(): Action
  {
    return Action::make('mark_as_paid')
      ->label('Mark as Paid')
      ->icon(Heroicon::OutlinedCurrencyDollar)
      ->color('success')
      ->modalHeading('Mark Subscription as Paid')
      ->modalWidth(Width::Medium)
      ->form([
        TextInput::make('amount')
          ->label('Amount')
          ->required()
          ->numeric()
          ->live(onBlur: true)
          ->hint(fn(?string $state) => toIndonesianCurrency($state ?? 0))
          ->default(fn(Subscription $record) => $record->amount),
        Select::make('payment_account_id')
          ->label('Payment Account')
          ->options(PaymentAccount::where('user_id', auth()->id())->orderBy('name', 'asc')->pluck('name', 'id'))
          ->required()
          ->native(false)
          ->live()
          ->hint(fn(?string $state) => toIndonesianCurrency(PaymentAccount::find($state ?? -1)?->deposit ?? 0))
          ->default(fn(Subscription $record) => $record->payment_account_id),
      ])
      ->action(function (Subscription $record, array $data): void {
        try {
          Payment::create([
            'code'                => getCode('payment'),
            'user_id'             => auth()->id(),
            'type_id'             => PaymentType::EXPENSE,
            'payment_account_id'  => $data['payment_account_id'],
            'name'                => 'Subscription: ' . $record->name,
            'amount'              => $data['amount'],
            'date'                => Carbon::now()->format('Y-m-d'),
          ]);

          $record->update(['next_date' => self::getNextDate($record->next_date, $record->cycle)]);

          Notification::make()
            ->success()
            ->title('Subscription Marked as Paid')
            ->body('Payment has been created and next date has been advanced.')
            ->send();
        } catch (ValidationException $e) {
          Notification::make()
            ->danger()
            ->title('Payment Failed')
            ->body('The payment could not be created because the balance is insufficient.')
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

  private static function getNextDate(string $currentNextDate, string $cycle): string
  {
    $date = Carbon::parse($currentNextDate);

    return match ($cycle) {
      'monthly'   => $date->addMonth()->format('Y-m-d'),
      'quarterly' => $date->addMonths(3)->format('Y-m-d'),
      'yearly'    => $date->addYear()->format('Y-m-d'),
    };
  }
}