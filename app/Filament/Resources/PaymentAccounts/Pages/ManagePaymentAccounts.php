<?php

namespace App\Filament\Resources\PaymentAccounts\Pages;

use App\Filament\Resources\PaymentAccounts\PaymentAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
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
}
