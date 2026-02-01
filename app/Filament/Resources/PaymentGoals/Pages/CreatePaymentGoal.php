<?php

namespace App\Filament\Resources\PaymentGoals\Pages;

use App\Filament\Resources\PaymentGoals\PaymentGoalResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentGoal extends CreateRecord
{
  protected static string $resource = PaymentGoalResource::class;

  protected function getRedirectUrl(): string
  {
    $resource = static::getResource();
    return $resource::getUrl('index');
  }
}
