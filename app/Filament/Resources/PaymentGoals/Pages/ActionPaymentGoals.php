<?php

namespace App\Filament\Resources\PaymentGoals\Pages;

use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Models\PaymentGoal;
use App\Models\PaymentGoalStatus;
use App\Models\PaymentType;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\DB;

class ActionPaymentGoals
{
  public static function fund(): Action
  {
    return Action::make('fund')
      ->label('Add Fund')
      ->icon('heroicon-o-banknotes')
      ->color('success')
      ->modalHeading(fn(PaymentGoal $record) => 'Add Fund: ' . $record->name)
      ->modalWidth(Width::Large)
      ->form(fn(Schema $schema) => $schema->components([
        Grid::make(2)
          ->schema([
            TextInput::make('current_amount_display')
              ->label('Current Amount')
              ->readOnly()
              ->dehydrated(false),
            TextInput::make('target_amount_display')
              ->label('Target Amount')
              ->readOnly()
              ->dehydrated(false),
            TextInput::make('remaining_amount_display')
              ->label('Remaining Needed')
              ->readOnly()
              ->dehydrated(false)
              ->columnSpanFull(),
          ]),
        Select::make('payment_account_id')
          ->label('Payment Account')
          ->required()
          ->searchable()
          ->preload()
          ->options(fn() => self::getPaymentAccount())
          ->native(false),
        TextInput::make('amount')
          ->label('Fund Amount')
          ->required()
          ->numeric()
          ->minValue(1)
          ->prefix('Rp')
          ->live(onBlur: true)
          ->hint(fn(?string $state) => toIndonesianCurrency($state ?? 0)),
      ]))
      ->fillForm(fn(PaymentGoal $record): array => [
        'current_amount_display' => toIndonesianCurrency($record->amount ?? 0),
        'target_amount_display' => toIndonesianCurrency($record->target_amount ?? 0),
        'remaining_amount_display' => toIndonesianCurrency(max($record->target_amount - $record->amount, 0)),
      ])
      ->action(function (Action $action, PaymentGoal $record, array $data): void {
        $amount = (int) ($data['amount'] ?? 0);
        $paymentAccountId = (int) ($data['payment_account_id'] ?? 0);

        if ($amount < 1) {
          Notification::make()
            ->danger()
            ->title('Invalid amount')
            ->body('The fund amount must be greater than zero.')
            ->send();

          $action->halt();
          return;
        }

        $paymentAccount = PaymentAccount::find($paymentAccountId);

        if (!$paymentAccount) {
          Notification::make()
            ->danger()
            ->title('Payment account not found')
            ->send();

          $action->halt();
          return;
        }

        $remainingNeeded = max($record->target_amount - $record->amount, 0);

        if ($remainingNeeded <= 0) {
          Notification::make()
            ->danger()
            ->title('Goal already completed')
            ->body('There is no remaining amount needed for this goal.')
            ->send();

          $action->halt();
          return;
        }

        if ($amount > $remainingNeeded) {
          Notification::make()
            ->danger()
            ->title('Amount exceeds remaining need')
            ->body('Remaining needed: ' . toIndonesianCurrency($remainingNeeded))
            ->send();

          $action->halt();
          return;
        }

        if ($paymentAccount->deposit < $amount) {
          Notification::make()
            ->danger()
            ->title('Insufficient balance')
            ->body('Available balance: ' . toIndonesianCurrency($paymentAccount->deposit ?? 0))
            ->send();

          $action->halt();
          return;
        }

        $paymentData = [
          'amount'             => $amount,
          'date'               => now()->format('Y-m-d'),
          'name'               => "Contribution : {$record->name} ({$record->code})",
          'type_id'            => PaymentType::EXPENSE,
          'payment_account_id' => $paymentAccountId,
          'has_items'          => false,
          'has_charge'         => false,
          'is_scheduled'       => false,
          'attachments'        => [],
        ];

        DB::beginTransaction();

        try {
          $mutated = Payment::mutateDataPayment($paymentData);

          if (!$mutated['status']) {
            Notification::make()
              ->danger()
              ->title('Failed to add fund')
              ->body($mutated['message'])
              ->send();

            DB::rollBack();
            $action->halt();
            return;
          }

          Payment::create($mutated['data']);

          $newAmount = $record->amount + $amount;
          $target = $record->target_amount;
          $progressPercent = $target > 0 ? round(($newAmount / $target) * 100, 2) : 0;

          $updates = [
            'amount' => $newAmount,
            'progress_percent' => $progressPercent,
          ];

          if ($progressPercent >= 100) {
            $updates['status_id'] = PaymentGoalStatus::COMPLETED;
          }

          $record->update($updates);

          DB::commit();

          $action->success();

          Notification::make()
            ->success()
            ->title('Fund added successfully')
            ->body('Added ' . toIndonesianCurrency($amount) . ' to this goal.')
            ->send();
        } catch (\Throwable $exception) {
          DB::rollBack();

          report($exception);

          Notification::make()
            ->danger()
            ->title('Unexpected error')
            ->body('Unable to add fund at the moment. Please try again later.')
            ->send();

          $action->halt();
        }
      });
  }

  protected static function getPaymentAccount(): array
  {
    $user = getUser();

    $list = PaymentAccount::where('user_id', $user->id)
      ->orderBy('deposit', 'DESC')
      ->get()
      ->mapWithKeys(fn(PaymentAccount $account): array => [
        $account->id => $account->name . ' (' . toIndonesianCurrency($account->deposit ?? 0) . ')'
      ]);

    return $list->toArray();
  }
}
