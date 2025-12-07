<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Payment;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePayment extends CreateRecord
{
  protected static string $resource = PaymentResource::class;

  protected function mutateFormDataBeforeCreate(array $data): array
  {
    $payment = new Payment();

    $mutate = $payment::mutateDataPayment($data);
    $data = $mutate['data'];

    if ($mutate['status'] == false) {
      Notification::make()
        ->danger()
        ->title('Transaction Failed!')
        ->body($mutate['message'] ?? 'Something went wrong!')
        ->send();

      $this->halt();
    }

    return $data;
  }

  protected function afterCreate(): void
  {
    $record = $this->record;
    $attachments = $record->attachments ?? [];

    if (!empty($attachments)) {
      foreach ($attachments as $attachment) {
        $file = $attachment;
        uploadAndOptimize($file, 'public', 'images/payment');
      }
    }
  }
  
  protected function getRedirectUrl(): string
  {
    $resource = static::getResource();
    $record = $this->getRecord();

    if ($record->has_items)
      return $resource::getUrl('edit', ['record' => $record]);

    return $resource::getUrl('index');
  }
}
