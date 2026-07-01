<?php

namespace App\Filament\Resources\PaymentAccounts\Pages;

use App\Filament\Resources\PaymentAccounts\PaymentAccountResource;
use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Models\PaymentType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

class ManagePaymentAccounts extends ManageRecords
{
  protected static string $resource = PaymentAccountResource::class;

  protected function getHeaderActions(): array
  {
    return [
      Action::make('close_periode')
        ->label('Close Periode')
        ->color('primary')
        ->requiresConfirmation()
        ->form([
          Select::make('periode')
            ->label('Periode (Month)')
            ->options(function () {
              $now = Carbon::now();
              $lastMonth = $now->copy()->subMonth();
              return [
                $lastMonth->format('Y-m') => $lastMonth->translatedFormat('F Y'),
                $now->format('Y-m') => $now->translatedFormat('F Y'),
              ];
            })
            ->required()
            ->native(false)
            ->default(Carbon::now()->format('Y-m')),
        ])
        ->action(fn(array $data) => $this->closePeriode($data)),

			CreateAction::make()
        ->modalWidth(Width::ExtraLarge),
    ];
  }

  // ! Audit
  public static function formAudit(Schema $schema): Schema
  {
    return $schema
      ->components([
        TextInput::make('current_deposit')
          ->readOnly()
          ->hint(fn($state) => toIndonesianCurrency((float) $state ?? 0)),
        TextInput::make('deposit')
          ->required()
          ->integer()
          ->numeric()
          ->minValue(0)
          ->autofocus()
          ->hint(fn($state) => toIndonesianCurrency((float) $state ?? 0))
          ->live(onBlur: true)
          ->afterStateUpdated(function (string $state, Get $get, Set $set) {
            $diff = (float) $get('current_deposit') - (float) $state;
            $diff = $diff > 0 ? -$diff : abs($diff);

            $set('diff_deposit', (int) $diff);
          }),
        TextInput::make('diff_deposit')
          ->readOnly()
          ->hint(fn($state) => toIndonesianCurrency((float) $state ?? 0)),
      ])
      ->columns(1);
  }

  public static function fillFormAudit(PaymentAccount $record): array
  {
    return [
      'current_deposit' => $record->deposit ?? 0,
      'deposit'         => $record->deposit ?? 0,
      'diff_deposit'    => 0
    ];
  }

  public static function actionAudit(Action $action, PaymentAccount $record, array $data): void
  {
    $record->audit($data['deposit']);

    $action->success();

    Notification::make()
      ->success()
      ->title('Audit Success')
      ->body('Audit has been successfully saved.')
      ->send();
  }

  public function closePeriode(array $data): void
  {
    $periode = $data['periode'];
    $endOfMonthDate = Carbon::createFromFormat('Y-m', $periode)->endOfMonth()->format('Y-m-d');
    $userId = auth()->id();
    $accounts = PaymentAccount::where('user_id', $userId)->get();

    DB::transaction(function () use ($accounts, $endOfMonthDate, $userId) {
      foreach ($accounts as $account) {
        $currentDeposit = (float) $account->deposit;
        if ($currentDeposit == 0) {
          continue;
        }

        $diffDeposit = $currentDeposit - 0;
        $paymentType = $diffDeposit > 0 ? PaymentType::EXPENSE : PaymentType::INCOME;

        Payment::create([
          'code'               => getCode('payment'),
          'name'               => 'Close Periode : ' . $account->name,
          'type_id'            => $paymentType,
          'user_id'            => $userId,
          'payment_account_id' => $account->id,
          'amount'             => abs($diffDeposit),
          'has_items'          => false,
          'attachments'        => [],
          'date'               => $endOfMonthDate,
        ]);
      }
    });

    Notification::make()
      ->success()
      ->title('Successfully Close Periode')
      ->body('Periode has been successfully closed and all account balances are reset to 0.')
      ->send();
  }
}
