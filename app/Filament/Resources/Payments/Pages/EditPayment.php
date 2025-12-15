<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use Illuminate\Support\Facades\Storage;
use App\Models\Payment;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Livewire\Attributes\On;

class EditPayment extends EditRecord
{
  protected static string $resource = PaymentResource::class;

  protected function getHeaderActions(): array
  {
    return [
      ViewAction::make(),
      DeleteAction::make(),
      ForceDeleteAction::make(),
      RestoreAction::make(),
    ];
  }

  #[On('refreshForm')]
  public function refreshForm(): void
  {
    $data = $this->record->toArray();
    parent::refreshFormData(array_keys($data));
  }

  protected function getRedirectUrl(): string
  {
    $resource = static::getResource();
    return $resource::getUrl('index');
  }

  protected function mutateFormDataBeforeSave(array $data): array
  {
    return $data;
  }

  protected function beforeSave(): void
  {
    $record = $this->record;
    $data = $this->data;

    // ! If items are present, this process will be handled in the ItemsRelationManager.
    if (!$record->has_items) {
      $mutate = Payment::mutateDataPaymentUpdate($record, $data);

      if (!$mutate['status']) {
        Notification::make()
          ->danger()
          ->title('Transaction Failed!')
          ->body($mutate['message'] ?? 'Something went wrong!')
          ->send();

        $this->halt();
      }
    }

    // ! See if there are any changes to the attachments
    $removedAttachments = array_diff($record->attachments ?? [], $data['attachments'] ?? []);

    // ? Has removed attachments
    if (!empty($removedAttachments)) {
      foreach ($removedAttachments as $attachment) {
        // ? Doesnt exist
        if (!Storage::disk('public')->exists($attachment))
          continue;

        // ! Delete attachment
        Storage::disk('public')->delete($attachment);
      }
    }
  }
}
