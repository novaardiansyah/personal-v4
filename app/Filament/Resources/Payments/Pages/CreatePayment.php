<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Gallery;
use App\Models\Payment;
use Filament\Resources\Pages\CreateRecord;

class CreatePayment extends CreateRecord
{
  protected static string $resource = PaymentResource::class;

  protected function getRedirectUrl(): string
  {
    $resource = static::getResource();
    $record = $this->getRecord();

    if ($record->has_items)
      return $resource::getUrl('edit', ['record' => $record]);

    return $resource::getUrl('index');
  }
}
