<?php

/*
 * Project Name: personal-v4
 * File: PaymentAction.php
 * Created Date: Thursday December 11th 2025
 * 
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 * 
 * Copyright (c) 2025-2026 Nova Ardiansyah, Org
 */

namespace App\Filament\Resources\Payments\Schemas;

use App\Jobs\PaymentResource\DailyReportJob;
use App\Jobs\PaymentResource\MonthlyReportJob;
use App\Jobs\PaymentResource\PaymentReportPdf;
use App\Models\Item;
use App\Models\ItemType;
use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Models\PaymentType;
use App\Services\PaymentService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;

class PaymentAction
{
  private static function _afterItemAttach(array $data, Model $record, RelationManager $livewire, $action)
  {
    $owner = $livewire->getOwnerRecord();

    PaymentService::afterItemAttach($owner, $record, [
      'quantity' => $data['quantity'],
      'price'    => $data['amount'],
      'total'    => $data['total'],
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
    $record->save();
    $record->load(['payment_account', 'payment_account_to']);

    if ($data['approve_draft']) {
      $mutate = PaymentService::manageDraft($record, false);

      if (!$mutate['status']) {
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

  // ! PrintPdf
  public static function printPdfSchema(Schema $schema): Schema
  {
    return $schema
      ->components([
        Select::make('report_type')
          ->label('Report Type')
          ->options([
            'date_range' => 'Custom Date Range',
            'daily' => 'Daily Report',
            'monthly' => 'Monthly Report',
          ])
          ->default('monthly')
          ->required()
          ->live()
          ->native(false),
        DatePicker::make('start_date')
          ->label('Start Date')
          ->required()
          ->native(false)
          ->default(Carbon::now()->startOfMonth())
          ->visible(fn($get) => $get('report_type') === 'date_range'),
        DatePicker::make('end_date')
          ->label('End Date')
          ->required()
          ->native(false)
          ->default(Carbon::now()->endOfMonth())
          ->visible(fn($get) => $get('report_type') === 'date_range'),
        Select::make('periode')
          ->label('Periode (Month)')
          ->options(function () {
            $options = [];
            $now = Carbon::now();
            $start = $now->copy()->subMonths(12);
            $end = $now->copy()->addMonths(12);
            while ($start->lte($end)) {
              $options[$start->format('Y-m')] = $start->translatedFormat('F Y');
              $start->addMonth();
            }
            return $options;
          })
          ->required()
          ->native(false)
          ->default(Carbon::now()->format('Y-m'))
          ->visible(fn($get) => $get('report_type') === 'monthly'),
        Toggle::make('send_to_email')
          ->label('Send to Email')
          ->default(true),
      ]);
  }

  public static function printPdfAction(Action $action, array $data): void
  {
    $user = getUser();
    $send_to_email = $data['send_to_email'] ?? false;

    $sendTo = [
      'send_to_email' => $send_to_email,
      'user' => $user,
      'notification' => true,
    ];

    match ($data['report_type']) {
      'daily' => DailyReportJob::dispatch($sendTo),
      'monthly' => MonthlyReportJob::dispatch(array_merge($sendTo, ['periode' => $data['periode']])),
      default => PaymentReportPdf::dispatch(array_merge($sendTo, [
        'start_date' => $data['start_date'],
        'end_date' => $data['end_date'],
      ])),
    };

    $messages = [
      'daily' => 'Daily report will be sent to your email.',
      'monthly' => 'Monthly report will be sent to your email.',
      'date_range' => 'Custom report will be sent to your email.',
      'default' => 'You will receive a notification when the report is ready.',
    ];

    if (!$send_to_email) {
      $data['report_type'] = 'default';
    }

    Notification::make()
      ->title('Report in process')
      ->body($messages[$data['report_type']])
      ->success()
      ->send();
  }
  // ! End PrintPdf

  public static function itemCreateAction()
  {
    return CreateAction::make()
      ->modalWidth(Width::FourExtraLarge)
      ->form(function (Schema $schema): Schema {
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
              'sm' => 3,
              'xs' => 1
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
      })
      ->mutateFormDataUsing(function (array $data, CreateAction $action): array {
        $item = Item::where('name', $data['name'])->first();

        if ($item) {
          Notification::make()
            ->title('Product or Service already exists!')
            ->danger()
            ->send();

          $action->halt();
        }

        $data['code'] = getCode('item');
        $data['item_code'] = getCode('payment_item');
        $data['price'] = $data['amount'];

        return $data;
      })
      ->after(function (array $data, Model $record, RelationManager $livewire, CreateAction $action): void {
        self::_afterItemAttach($data, $record, $livewire, $action);
      });
  }

  public static function itemEditAction()
  {
    return EditAction::make()
      ->modalWidth(Width::ThreeExtraLarge)
      ->form(function (Schema $schema, Model $record): Schema {
        return $schema
          ->components([
            TextInput::make('name')
              ->default($record->name)
              ->disabled(),

            Grid::make([
              'sm' => 3,
              'xs' => 1
            ])
              ->schema([
                TextInput::make('amount')
                  ->label('Price')
                  ->required()
                  ->numeric()
                  ->minValue(0)
                  ->default($record->pivot->price)
                  ->live(onBlur: true)
                  ->afterStateUpdated(function ($state, $set, $get): void {
                    $get('quantity') && $set('total', $state * $get('quantity'));
                  })
                  ->hint(fn(?string $state) => toIndonesianCurrency($state ?? 0)),

                TextInput::make('quantity')
                  ->label('Qty')
                  ->required()
                  ->numeric()
                  ->minValue(1)
                  ->default($record->pivot->quantity)
                  ->live(onBlur: true)
                  ->afterStateUpdated(function ($state, $set, $get): void {
                    $get('amount') && $set('total', $state * $get('amount'));
                  })
                  ->hint(fn(?string $state) => number_format($state ?? 0, 0, ',', '.')),

                TextInput::make('total')
                  ->label('Total')
                  ->numeric()
                  ->minValue(0)
                  ->default($record->pivot->total)
                  ->live(onBlur: true)
                  ->readOnly()
                  ->hint(fn(?string $state) => toIndonesianCurrency($state ?? 0)),
              ])
              ->columnSpanFull()
          ])
          ->columns(1);
      })
      ->action(function (array $data, Model $record, RelationManager $livewire, EditAction $action): void {
        PaymentService::updateItemPivot($livewire->getOwnerRecord(), $record, $data);
        $action->getLivewire()->dispatch('refreshForm');
      });
  }

  public static function attachAction(): AttachAction
  {
    return AttachAction::make()
      ->modalWidth(Width::ThreeExtraLarge)
      ->form(function (Schema $schema): Schema {
        return $schema
          ->components([
            Select::make('recordId')
              ->label('Product / Service')
              ->native(false)
              ->options(function () {
                return Item::latest('updated_at')->pluck('name', 'id');
              })
              ->preload()
              ->required()
              ->live(onBlur: true)
              ->afterStateUpdated(function (?string $state, Set $set, Get $get) {
                logger('state: '.$state);
                if (!$state) return;
                
                $item = Item::where('id', $state)->first();

                if (!$item) return;

                $set('amount', $item->amount);
                $get('quantity') && $set('total', $item->amount * $get('quantity'));
              }),

            TextInput::make('amount')
              ->required()
              ->numeric()
              ->minValue(0)
              ->live(onBlur: true)
              ->afterStateUpdated(function ($state, $set, $get): void {
                $get('quantity') && $set('total', $state * $get('quantity'));
              })
              ->hint(fn(?string $state) => toIndonesianCurrency(((float) $state ?? 0))),

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
              ->hint(fn(?string $state) => number_format(((float) $state ?? 0), 0, ',', '.')),

            TextInput::make('total')
              ->label('Total')
              ->required()
              ->numeric()
              ->minValue(0)
              ->live(onBlur: true)
              ->readOnly()
              ->hint(fn(?string $state) => toIndonesianCurrency(((float) $state ?? 0))),
          ])
          ->columns(2);
      })
      ->mutateFormDataUsing(function (array $data): array {
         $data['price'] = $data['amount'] ?? 0;
         $data['item_code'] = getCode('payment_item');
         return $data;
      })
      ->after(function (array $data, Model $record, RelationManager $livewire, AttachAction $action) {
        self::_afterItemAttach($data, $record, $livewire, $action);
      });
  }

  public static function detachAction()
  {
    return DetachAction::make()
      ->color('danger')
      ->before(function (Model $record, RelationManager $livewire, DetachAction $action): void {
        $owner = $livewire->getOwnerRecord();

        PaymentService::beforeItemDetach($owner, $record, [
          'quantity' => $record->quantity,
          'total'    => $record->pivot_total,
        ]);

        $action->getLivewire()->dispatch('refreshForm');
      });
  }
}
