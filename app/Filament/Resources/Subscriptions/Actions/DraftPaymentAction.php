<?php

namespace App\Filament\Resources\Subscriptions\Actions;

use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Models\PaymentType;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;

class DraftPaymentAction
{
  public static function make(): Action
  {
    return Action::make('draft_payment')
      ->label('Draft Payment')
      ->icon(Heroicon::OutlinedDocumentPlus)
      ->color('info')
      ->modalHeading('Draft Payment')
      ->modalWidth(Width::Medium)
      ->schema([
        DatePicker::make('date')
          ->label('Date')
          ->required()
          ->native(false)
          ->displayFormat('M d, Y')
          ->closeOnDateSelection(),

        TextInput::make('amount')
          ->label('Amount')
          ->required()
          ->numeric()
          ->live(onBlur: true)
          ->hint(fn(?string $state) => toIndonesianCurrency($state ?? 0)),

        Select::make('payment_account_id')
          ->label('Payment Account')
          ->options(fn() => PaymentAccount::where('user_id', auth()->id())->orderBy('name', 'asc')->pluck('name', 'id'))
          ->required()
          ->native(false)
          ->searchable()
          ->preload()
          ->live(onBlur: true)
          ->hint(fn(?string $state) => toIndonesianCurrency(PaymentAccount::find($state ?? -1)?->deposit ?? 0)),
      ])
      ->fillForm(fn(Subscription $record): array => [
        'date'               => $record->next_date ? Carbon::parse($record->next_date)->toDateString() : Carbon::now()->toDateString(),
        'amount'             => $record->amount,
        'payment_account_id' => $record->payment_account_id,
      ])
      ->action(function (Subscription $record, array $data): void {
        $period = $record->getPeriodForDate($data['date']);

        if ($record->hasDraftPaymentForPeriod($period)) {
          Notification::make()
            ->warning()
            ->title('Draft Payment Exists')
            ->body("Draft payment for period {$period} has already been created.")
            ->send();

          return;
        }

        DB::transaction(function () use ($record, $data, $period) {
          $payment = Payment::create([
            'type_id'            => PaymentType::EXPENSE,
            'user_id'            => auth()->id(),
            'payment_account_id' => $data['payment_account_id'],
            'category_id'        => $record->category_id,
            'name'               => "Subscription: {$record->name}" . ($record->code ? " ({$record->code})" : ''),
            'amount'             => $data['amount'],
            'date'               => $data['date'],
            'is_draft'           => true,
            'is_scheduled'       => false,
          ]);

          SubscriptionPayment::create([
            'subscription_id' => $record->id,
            'payment_id'      => $payment->id,
            'period'          => $period,
          ]);
        });

        Notification::make()
          ->success()
          ->title('Draft Payment Created')
          ->body('Draft payment has been created successfully.')
          ->send();
      });
  }
}
