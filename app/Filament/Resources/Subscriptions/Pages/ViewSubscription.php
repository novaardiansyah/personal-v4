<?php

namespace App\Filament\Resources\Subscriptions\Pages;

use App\Filament\Resources\Subscriptions\Actions\DraftPaymentAction;
use App\Filament\Resources\Subscriptions\Actions\PauseResumeAction;
use App\Filament\Resources\Subscriptions\SubscriptionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSubscription extends ViewRecord
{
  protected static string $resource = SubscriptionResource::class;

  protected function getHeaderActions(): array
  {
    return [
      EditAction::make(),
      DraftPaymentAction::make(),
      PauseResumeAction::make(),
      DeleteAction::make(),
      RestoreAction::make(),
    ];
  }
}
