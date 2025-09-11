<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Models\Item;
use App\Models\Payment;
use App\Models\PaymentType;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PaymentAction
{
  // ! Delete
  public static function deleteAfter(Payment $record)
  {
    $attachments  = $record->attachments;
    $is_scheduled = $record->is_scheduled ?? false;

    if (PaymentType::TRANSFER == $record->type_id || PaymentType::WITHDRAWAL == $record->type_id)
    {
      $balanceOrigin = $record->payment_account->deposit + $record->amount;
      $balanceTo     = $record->payment_account_to - $record->amount;

      if (!$is_scheduled) {
        $record->payment_account->update([
          'deposit' => $balanceOrigin
        ]);

        $record->payment_account_to->update([
          'deposit' => $balanceTo
        ]);
      }
    } else if (PaymentType::EXPENSE == $record->type_id || PaymentType::INCOME == $record->type_id) {
      $adjustment    = ($record->type_id == PaymentType::EXPENSE) ? +$record->amount : -$record->amount;
      $depositChange = ($record->payment_account->deposit + $adjustment);

      if (!$is_scheduled) {
        $record->payment_account->update([
          'deposit' => $depositChange
        ]);
      }
    }

    // ! Has attachments
    if (!empty($attachments)) {
      foreach ($attachments as $attachment) {
        // ! Doesnt exist
        if (!Storage::disk('public')->exists($attachment))
          continue;

        // ! Delete attachment
        Storage::disk('public')->delete($attachment);
      }
    }
  }
  // ! End Delete

  // ! ItemRelationManager::Attach
  public static function ItemAttachRecordSelect(Select $select)
  {
    return $select
      ->placeholder('Product & Service')
      ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Search product or service using {name} or {Product & Service ID} in Items menu.')
      ->live(onBlur: true)
      ->afterStateUpdated(function($state, $set, $get): void {
        $item = Item::find($state ?? 0);

        if ($item) {
          $set('amount', $item->amount);
          $get('quantity') && $set('total', $item->amount * $get('quantity'));
        }
      });
  }

  public static function itemAttachForm(Schema $schema, $action)
  {
    return $schema
      ->components([
        $action->getRecordSelect(),

        TextInput::make('amount')
          ->required()
          ->numeric()
          ->minValue(0)
          ->live(onBlur: true)
          ->afterStateUpdated(function($state, $set, $get): void {
            $get('quantity') && $set('total', $state * $get('quantity'));
          })
          ->hint(fn (?string $state) => toIndonesianCurrency($state ?? 0)),

        TextInput::make('quantity')
          ->label('Qty')
          ->required()
          ->numeric()
          ->default(1)
          ->minValue(0)
          ->live(onBlur: true)
          ->afterStateUpdated(function($state, $set, $get): void {
            $get('amount') && $set('total', $state * $get('amount'));
          })
          ->hint(fn (?string $state) => number_format($state ?? 0, 0, ',', '.')),

        TextInput::make('total')
          ->label('Total')
          ->required()
          ->numeric()
          ->minValue(0)
          ->live(onBlur: true)
          ->readOnly()
          ->hint(fn (?string $state) => toIndonesianCurrency($state ?? 0)),
      ])
      ->columns(2);
  }

  public static function itemAttachMutateFormDataUsing(array $data): array
  {
    $data['price'] = $data['amount'] ?? 0;
    $data['item_code'] = getCode('payment_item');
    return $data;
  }

  public static function itemAttachAfter(array $data, Model $record, RelationManager $livewire, AttachAction $action)
  {
    $owner = $livewire->getOwnerRecord();

    // * Update item price
    $record->update(['amount' => $data['amount']]);

    // * Count total expense
    $expense = $owner->amount + (int) $data['total'];

    // * Count deposit change
    $adjustedDeposit = $owner->payment_account->deposit + $owner->amount - $expense;

    $is_scheduled = $owner->is_scheduled ?? false;

    if (!$is_scheduled) {
      // * Update deposit payment account
      $owner->payment_account->update(['deposit' => $adjustedDeposit]);
    }

    // * Add item name to Notes parent form
    $note = trim(($owner->name ?? '') . ', ' . "{$record->name} (x{$data['quantity']})", ', ');

    // * Update notes and amount
    $owner->update(['amount' => $expense, 'name' => $note]);

    // * refresh parent form
    $action->getLivewire()->dispatch('refreshForm');
  }
  // ! End ItemRelationManager::Attach

  // ! ItemRelationManager::Detach
  public static function itemDetachBefore(Model $record, RelationManager $livewire, DetachAction $action): void
  {
    $owner = $livewire->getOwnerRecord();

    // * Count expense after detach
    $expense = $owner->amount - $record->pivot_total;

    // * Count deposit change
    $adjustedDeposit = $owner->payment_account->deposit + $owner->amount - $expense;

    $is_scheduled = $owner->is_scheduled ?? false;

    if (!$is_scheduled) {
      // * Update deposit payment account
      $owner->payment_account->update(['deposit' => $adjustedDeposit]);
    }

    $itemName = $record->name . ' (x' . $record->quantity . ')';
    $note = trim(implode(', ', array_diff(explode(', ', $owner->name ?? ''), [$itemName])));

    // * Update notes and amount
    $owner->update(['amount' => $expense, 'name' => $note]);

    // * refresh parent form
    $action->getLivewire()->dispatch('refreshForm');
  }
  // ! ItemRelationManager::Detach
}