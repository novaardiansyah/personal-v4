<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPayment extends ViewRecord
{
  protected static string $resource = PaymentResource::class;

  protected function getHeaderActions(): array
  {
    return [
			CreateAction::make(),
      EditAction::make(),
    ];
  }

  public function getTitle(): string
  {
    return 'View Details';
  }
}
