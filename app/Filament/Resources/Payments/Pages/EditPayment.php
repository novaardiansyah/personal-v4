<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\Actions\PaymentAction;
use App\Filament\Resources\Payments\PaymentResource;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Livewire\Attributes\On;

class EditPayment extends EditRecord
{
  protected static string $resource = PaymentResource::class;

  protected function getHeaderActions(): array
  {
    return [
			ViewAction::make(),
			CreateAction::make(),
			PaymentAction::manageDraft()
				->color('primary')
				->icon(null),
      DeleteAction::make(),
      ForceDeleteAction::make(),
      RestoreAction::make(),
    ];
  }

  #[On('refreshForm')]
  public function refreshForm(): void
  {
    $this->record = $this->record->fresh();
    $this->fillForm();
  }

  protected function getRedirectUrl(): string
  {
    $resource = static::getResource();
    return $resource::getUrl('index');
  }
}
