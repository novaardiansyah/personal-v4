<?php

namespace App\Filament\Resources\PaymentGoals\Pages;

use App\Filament\Resources\PaymentGoals\PaymentGoalResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditPaymentGoal extends EditRecord
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

  protected function getRedirectUrl(): string
  {
    $resource = static::getResource();
    return $resource::getUrl('index');
  }
}
