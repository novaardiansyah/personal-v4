<?php

namespace App\Filament\Resources\PaymentAccounts\Pages;

use App\Filament\Resources\PaymentAccounts\PaymentAccountResource;
use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Models\PaymentType;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
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
  // ! End Audit
}
