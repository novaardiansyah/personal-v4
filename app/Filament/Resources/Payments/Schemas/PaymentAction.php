<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Models\Item;
use App\Models\ItemType;
use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Models\PaymentType;
use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DetachAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Services\PaymentService;

class PaymentAction
{
  // ! ItemRelationManager::Attach
  public static function ItemAttachRecordSelect(Select $select)
  {
    return $select
      ->placeholder('Product & Service')
      ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Search product or service using {name} or {Product & Service ID} in Items menu.')
      ->live(onBlur: true)
      ->afterStateUpdated(function ($state, $set, $get): void {
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
          ->afterStateUpdated(function ($state, $set, $get): void {
            $get('quantity') && $set('total', $state * $get('quantity'));
          })
          ->hint(fn(?string $state) => toIndonesianCurrency($state ?? 0)),

        TextInput::make('quantity')
          ->label('Qty')
          ->required()
          ->numeric()
          ->default(1)
          ->minValue(0)
          ->live(onBlur: true)
          ->afterStateUpdated(function ($state, $set, $get): void {
            $get('amount') && $set('total', $state * $get('amount'));
          })
          ->hint(fn(?string $state) => number_format($state ?? 0, 0, ',', '.')),

        TextInput::make('total')
          ->label('Total')
          ->required()
          ->numeric()
          ->minValue(0)
          ->live(onBlur: true)
          ->readOnly()
          ->hint(fn(?string $state) => toIndonesianCurrency($state ?? 0)),
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
    $owner = $livewire->getOwnerRecord();

    PaymentService::beforeItemDetach($owner, $record, [
      'quantity' => $record->quantity,
      'total' => $record->pivot_total,
      'has_charge' => $record->has_charge ?? false,
    ]);

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
              ->afterStateUpdated(function ($state, $set, $get): void {
                $get('quantity') && $set('total', $state * $get('quantity'));
              })
              ->hint(fn(?string $state) => toIndonesianCurrency($state ?? 0)),

            TextInput::make('quantity')
              ->required()
              ->numeric()
              ->default(1)
              ->minValue(0)
              ->live(onBlur: true)
              ->afterStateUpdated(function ($state, $set, $get): void {
                $get('amount') && $set('total', $state * $get('amount'));
              })
              ->hint(fn(?string $state) => number_format($state ?? 0, 0, ',', '.')),

            TextInput::make('total')
              ->label('Total')
              ->numeric()
              ->minValue(0)
              ->live(onBlur: true)
              ->readOnly()
              ->hint(fn(?string $state) => toIndonesianCurrency($state ?? 0)),
          ])
          ->columnSpanFull()
      ])
      ->columns(2);
  }

  public static function itemMutateFormDataUsing(array $data): array
  {
    $data['code'] = getCode('item');
    $data['item_code'] = getCode('payment_item');
    $data['price'] = $data['amount'];

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

    PaymentService::afterItemAttach($owner, $record, [
      'quantity' => $data['quantity'],
      'price' => $data['amount'],
      'total' => $data['total'],
      'has_charge' => $record->has_charge ?? false,
    ]);

    $action->getLivewire()->dispatch('refreshForm');
  }

  // ! ManageDraft
  public static function manageDraftSchema(Schema $schema): Schema
  {
    return $schema
      ->components([
        TextInput::make('amount')
          ->label('Amount')
          ->required()
          ->numeric()
          ->live(onBlur: true)
          ->hint(fn(?string $state) => toIndonesianCurrency($state ?? 0)),

        Select::make('type_id')
          ->label('Type')
          ->options(PaymentType::all()->pluck('name', 'id'))
          ->required()
          ->native(false)
          ->live(),

        Select::make('payment_account_id')
          ->label('Payment Account')
          ->options(fn(Get $get) => PaymentAccount::where('id', '!=', $get('payment_account_to_id'))
            ->where('user_id', auth()->id())
            ->pluck('name', 'id'))
          ->required()
          ->native(false)
          ->live()
          ->hint(fn(?string $state) => toIndonesianCurrency(PaymentAccount::find($state ?? -1)?->deposit ?? 0)),

        Select::make('payment_account_to_id')
          ->label('Payment To')
          ->options(fn(Get $get) => PaymentAccount::where('id', '!=', $get('payment_account_id'))
            ->where('user_id', auth()->id())
            ->pluck('name', 'id'))
          ->required(fn(Get $get): bool => in_array($get('type_id'), [PaymentType::TRANSFER, PaymentType::WITHDRAWAL]))
          ->visible(fn(Get $get): bool => in_array($get('type_id'), [PaymentType::TRANSFER, PaymentType::WITHDRAWAL]))
          ->native(false)
          ->hint(fn(?string $state) => toIndonesianCurrency(PaymentAccount::find($state ?? -1)?->deposit ?? 0)),

        Toggle::make('approve_draft')
          ->label('Approve Draft')
          ->helperText('Jika draft disetujui, transaksi akan dijalankan dan saldo akan dimutasi.')
          ->default(false),
      ]);
  }

  public static function manageDraftFillForm(Payment $record): array
  {
    return [
      'amount' => $record->amount,
      'type_id' => $record->type_id,
      'payment_account_id' => $record->payment_account_id,
      'payment_account_to_id' => $record->payment_account_to_id,
      'approve_draft' => false,
    ];
  }

  public static function manageDraftAction(Action $action, Payment $record, array $data): void
  {
    $record->amount = intval($data['amount']);
    $record->type_id = intval($data['type_id']);
    $record->payment_account_id = intval($data['payment_account_id']);
    $record->payment_account_to_id = $data['payment_account_to_id'] ?? null;

    if ($data['approve_draft']) {
      $record->is_draft = false;
    }

    $record->save();
    $record->load(['payment_account', 'payment_account_to']);

    if ($data['approve_draft']) {
      $mutate = Payment::approveDraft($record);

      if (!$mutate['status']) {
        $record->is_draft = true;
        $record->save();

        Notification::make()
          ->danger()
          ->title('Transaction Failed!')
          ->body($mutate['message'] ?? 'Something went wrong!')
          ->send();

        $action->halt();
        return;
      }
    }

    Notification::make()
      ->success()
      ->title($data['approve_draft'] ? 'Draft Approved!' : 'Draft Updated!')
      ->body($data['approve_draft'] ? 'Draft has been approved and balance has been mutated.' : 'Draft has been updated successfully.')
      ->send();
  }
  // ! End ManageDraft
}
