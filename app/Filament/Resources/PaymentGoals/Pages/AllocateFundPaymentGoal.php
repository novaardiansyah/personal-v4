<?php

namespace App\Filament\Resources\PaymentGoals\Pages;

use App\Filament\Resources\PaymentGoals\PaymentGoalResource;
use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Models\PaymentGoalStatus;
use App\Models\PaymentType;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AllocateFundPaymentGoal extends EditRecord
{
  protected static string $resource = PaymentGoalResource::class;

  protected function getHeaderActions(): array
  {
    return [
      DeleteAction::make(),
      ForceDeleteAction::make(),
      RestoreAction::make(),
    ];
  }

  public function form(Schema $schema): Schema
  {
    return $schema
      ->components([
        Grid::make(2)
          ->schema([
            TextInput::make('target_amount_display')
              ->label('Target Fund')
              ->readOnly()
              ->dehydrated(false),
            TextInput::make('current_amount_display')
              ->label('Current Fund')
              ->readOnly()
              ->dehydrated(false),
            TextInput::make('remaining_amount_display')
              ->label('Remaining Fund')
              ->readOnly()
              ->dehydrated(false)
              ->columnSpanFull()
              ->live(onBlur: true)
              ->hint(function (Get $get) {
                $remaining = (int) $get('remaining_amount');
                $target    = (int) $get('target_amount');
                $percent   = $target > 0 ? round(($remaining / $target) * 100, 2) : 0;
                return $percent <= 0 ? '0%' : '-' . $percent . '%';
              }),
          ]),

        Grid::make(1)
          ->schema([
            Select::make('payment_account_id')
              ->label('Payment Account')
              ->required()
              ->searchable()
              ->preload()
              ->options(fn() => self::getPaymentAccount())
              ->native(false),
            TextInput::make('fund_amount')
              ->label('Allocate Fund')
              ->required()
              ->numeric()
              ->minValue(1)
              ->prefix('Rp')
              ->live(onBlur: true)
              ->hint(function (Get $get) {
                $state     = (int) $get('fund_amount');
                $target    = (int) $get('target_amount');
                $remaining = $target - $state;
                $percent   = $target > 0 ? round(($remaining / $target) * 100, 2) : 0;

                $percent    = (100 - $percent) > 0 ? number_format(100 - $percent, 2) : 0;
                $percentStr = $percent . '%';

                return toIndonesianCurrency($state) . ' (' . $percentStr . ')';
              })
              ->afterStateUpdated(function (?string $state, Set $set, Get $get) {
                $current = (int) $get('amount');
                $target = (int) $get('target_amount');

                $current = $current + (int) $state;
                $remaining = $target - $current;
                $set('remaining_amount', $remaining);

                $set('remaining_amount_display', toIndonesianCurrency($remaining));
              }),
          ])
      ]);
  }

  protected function fillForm(): void
  {
    $record = $this->getRecord();
    $record->current_amount_display = toIndonesianCurrency($record->amount ?? 0);
    $record->target_amount_display = toIndonesianCurrency($record->target_amount ?? 0);
    $record->remaining_amount = $record->target_amount - $record->amount ?? 0;
    $record->remaining_amount_display = toIndonesianCurrency($record->remaining_amount);
    $this->fillFormWithDataAndCallHooks($record);
  }

  protected function handleRecordUpdate(Model $record, array $data): Model
  {
    $amount = (int) ($data['fund_amount'] ?? 0);
    $remainingNeeded = max($record->target_amount - $record->amount, 0);

    if ($remainingNeeded <= 0) {
      Notification::make()
        ->danger()
        ->title('Goal already completed')
        ->body('There is no remaining amount needed for this goal.')
        ->send();

      $this->halt();
    }

    if ($amount > $remainingNeeded) {
      Notification::make()
        ->danger()
        ->title('Amount exceeds remaining fund')
        ->body('Remaining fund: ' . toIndonesianCurrency($remainingNeeded))
        ->send();

      $this->halt();
    }

    $paymentAccountId = $data['payment_account_id'];
    $paymentAccount = PaymentAccount::find($paymentAccountId);

    if ($paymentAccount->deposit < $amount) {
      Notification::make()
        ->danger()
        ->title('Insufficient balance')
        ->body('Your balance is not enough to allocate fund ' . toIndonesianCurrency($amount) . ' to this goal.')
        ->send();

      $this->halt();
    }

    $paymentData = [
      'amount'             => $amount,
      'date'               => now()->format('Y-m-d'),
      'name'               => "Allocate fund : {$record->name} ({$record->code})",
      'type_id'            => PaymentType::EXPENSE,
      'payment_account_id' => $paymentAccountId,
      'has_items'          => false,
      'has_charge'         => false,
      'is_scheduled'       => false,
      'attachments'        => [],
    ];

    DB::beginTransaction();

    try {
      Payment::create($paymentData);

      $newAmount = $record->amount + $amount;
      $progressPercent = $record->target_amount > 0 ? round(($newAmount / $record->target_amount) * 100, 2) : 0;

      $updates = [
        'amount' => $newAmount,
        'progress_percent' => $progressPercent,
      ];

      if ($progressPercent >= 100) {
        $updates['status_id'] = PaymentGoalStatus::COMPLETED;
      }

      $record->update($updates);

      DB::commit();
    } catch (\Throwable $exception) {
      DB::rollBack();

      $message = $exception->getMessage() ?? 'Unable to add fund at the moment. Please try again later.';

      logger("Error allocating fund {$record->name} ({$record->code}): {$message}");

      Notification::make()
        ->danger()
        ->title('Failed to allocate fund')
        ->body($message)
        ->send();

      $this->halt();
    }

    return $record;
  }

  protected function getSavedNotification(): ?Notification
  {
    return Notification::make()
      ->success()
      ->title('Saved')
      ->body('Fund allocated successfully');
  }

  protected function getRedirectUrl(): string
  {
    $resource = static::getResource();
    return $resource::getUrl('index');
  }

  protected static function getPaymentAccount(): array
  {
    $user = getUser();

    $list = PaymentAccount::where('user_id', $user->id)
      ->where('deposit', '>', 0)
      ->orderBy('deposit', 'DESC')
      ->get()
      ->mapWithKeys(fn(PaymentAccount $account): array => [
        $account->id => $account->name . ' (' . toIndonesianCurrency($account->deposit ?? 0) . ')'
      ]);

    return $list->toArray();
  }
}
