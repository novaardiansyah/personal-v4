<?php

namespace App\Filament\Resources\PaymentGoals\Pages;

use App\Filament\Resources\PaymentGoals\PaymentGoalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;

class ManagePaymentGoals extends ManageRecords
{
  protected static string $resource = PaymentGoalResource::class;

  protected function getHeaderActions(): array
  {
    return [
      CreateAction::make()
        ->modalWidth(Width::SixExtraLarge),
    ];
  }
}
