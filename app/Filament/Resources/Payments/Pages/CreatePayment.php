<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePayment extends CreateRecord
{
  protected static string $resource = PaymentResource::class;

  protected function getRedirectUrl(): string
  {
    $resource = static::getResource();
    $record = $this->getRecord();

    return $resource::getUrl('edit', ['record' => $record]);
  }
}
