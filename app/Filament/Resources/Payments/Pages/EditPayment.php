<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use Illuminate\Support\Facades\Storage;
use App\Models\PaymentType;
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
    $data   = $this->data;

    // ! If items are present, this process will be handled in the ItemsRelationManager.
    if (!$record->has_items) {
      $is_scheduled = $record->is_scheduled ?? false;
      $amount = intval($data['amount']);

      if ($record->type_id == PaymentType::EXPENSE || $record->type_id == PaymentType::INCOME) {
        $adjustment    = ($record->type_id == PaymentType::EXPENSE) ? +$record->amount : -$record->amount;
        $depositChange = ($record->payment_account->deposit + $adjustment);

        if ($depositChange < $amount && $depositChange != 0) {
          Notification::make()
            ->danger()
            ->title('Transaction Failed!')
            ->body('The amount in the payment account is not sufficient for the transaction.')
            ->send();

          $this->halt();
        }

        if ($record->type_id == PaymentType::EXPENSE) {
          $amount = -$amount;
        }

        $depositChange = $depositChange + $amount;
        
        if (!$is_scheduled) {
          $record->payment_account->update([
            'deposit' => $depositChange
          ]);
        }
      } else if ($record->type_id == PaymentType::TRANSFER || $record->type_id == PaymentType::WITHDRAWAL) {
        // ! Withdraw the balance from the destination account and return it to the origin account.
        $balanceTo     = $record->payment_account_to->deposit + $amount - $record->amount;
        $balanceOrigin = $record->payment_account->deposit + $record->amount;

        if ($balanceOrigin < $amount) {
          Notification::make()
            ->danger()
            ->title('Transaction Failed!')
            ->body('The amount in the payment account is not sufficient for the transaction.')
            ->send();

          $this->halt();
        }

        if (!$is_scheduled) {
          $record->payment_account->update([
            'deposit' => $balanceOrigin - $amount
          ]);

          $record->payment_account_to->update([
            'deposit' => $balanceTo
          ]);
        }
      } else {
        // ! NO ACTION
        Notification::make()
          ->danger()
          ->title('Transaction Failed!')
          ->body('The selected transaction type is invalid.')
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
