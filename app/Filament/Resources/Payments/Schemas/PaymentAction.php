<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Models\Item;
use App\Models\ItemType;
use App\Models\Payment;
use App\Models\PaymentType;
use Filament\Actions\AttachAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DetachAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PaymentAction
{
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
    return self::_afterItemAttach($data, $record, $livewire, $action);
  }
  // ! End ItemRelationManager::Attach

  // ! ItemRelationManager::Detach
  public static function itemDetachBefore(Model $record, RelationManager $livewire, DetachAction $action): void
  {
    $owner           = $livewire->getOwnerRecord();
    $expense         = $owner->amount - $record->pivot_total;
    $adjustedDeposit = $owner->payment_account->deposit + $owner->amount - $expense;

    $has_charge   = boolval($record->has_charge ?? 0);
    $is_scheduled = boolval($owner->is_scheduled ?? 0);
    $is_draft     = boolval($owner->is_draft ?? 0);

    if ($is_scheduled)
      $has_charge = true;

    if ($is_draft)
      $has_charge = true;

    if (!$has_charge) {
      $owner->payment_account->update(['deposit' => $adjustedDeposit]);
    }

    $itemName = $record->name . ' (x' . $record->quantity . ')';
    $note     = trim(implode(', ', array_diff(explode(', ', $owner->name ?? ''), [$itemName])));

    $owner->update(['amount' => $expense, 'name' => $note]);
    $action->getLivewire()->dispatch('refreshForm');
  }
  // ! ItemRelationManager::Detach

  // ! ItemRelationManager::CreateAction
  public static function itemCreateForm(Schema $schema, CreateAction $action)
  {
    return $schema
      ->components([
        TextInput::make('name')
          ->required()
          ->maxLength(255),

        Select::make('type_id')
          ->relationship('type', 'name')
          ->default(ItemType::PRODUCT)
          ->native(false)
          ->preload()
          ->required(),

        Grid::make([
          'default' => 3
        ])
          ->schema([
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
              ->required()
              ->numeric()
              ->default(1)
              ->minValue(0)
              ->live(onBlur: true)
              ->afterStateUpdated(function($state, $set, $get): void {
                $get('amount') && $set('total', $state * $get('amount'));
              })
              ->hint( fn (?string $state) => number_format($state ?? 0, 0, ',', '.')),

            TextInput::make('total')
              ->label('Total')
              ->numeric()
              ->minValue(0)
              ->live(onBlur: true)
              ->readOnly()
              ->hint(fn (?string $state) => toIndonesianCurrency($state ?? 0)),
          ])
          ->columnSpanFull()
      ])
      ->columns(2);
  }

  public static function itemMutateFormDataUsing(array $data): array
  {
    $data['code']      = getCode('item');
    $data['item_code'] = getCode('payment_item');
    $data['price']     = $data['amount'];

    return $data;
  }

  public static function itemCreateAfter(array $data, Model $record, RelationManager $livewire, CreateAction $action)
  {
    return self::_afterItemAttach($data, $record, $livewire, $action);
  }
  // ! ItemRelationManager::CreateAction

  private static function _afterItemAttach(array $data, Model $record, RelationManager $livewire, $action)
  {
    $owner = $livewire->getOwnerRecord();

    $record->update(['amount' => $data['amount']]);

    $expense         = $owner->amount + (int) $data['total'];
    $adjustedDeposit = $owner->payment_account->deposit + $owner->amount - $expense;

    $has_charge   = boolval($record->has_charge ?? 0);
    $is_scheduled = boolval($owner->is_scheduled ?? 0);
    $is_draft     = boolval($owner->is_draft ?? 0);

    if ($is_scheduled)
      $has_charge = true;

    if ($is_draft)
      $has_charge = true;

    if (!$has_charge) {
      $owner->payment_account->update(['deposit' => $adjustedDeposit]);
    }

    $note = trim(($owner->name ?? '') . ', ' . "{$record->name} (x{$data['quantity']})", ', ');

    $owner->update(['amount' => $expense, 'name' => $note]);
    $action->getLivewire()->dispatch('refreshForm');
  }
}