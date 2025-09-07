<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Models\PaymentAccount;
use App\Models\PaymentType;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class PaymentForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make([
          Grid::make([
            'default'  => 3
          ])
            ->columns(3)
            ->columnSpanFull()
            ->schema([
              Toggle::make('has_items')
                ->label('Product & Service')
                ->disabledOn('edit')
                ->live(onBlur: true)
                ->afterStateUpdated(function (Set $set, string $state): void {
                  if ($state) {
                    $set('amount', 0);
                    $set('type_id', 1);
                    $set('has_charge', false);
                  }
                }),
                
              Toggle::make('has_charge')
                ->label('Without Charge')
                ->disabled(function (callable $get, callable $set, string $operation) {
                  if ($operation === 'edit')
                    return true;
                  return $get('has_items');
                }),

              Toggle::make('is_scheduled')
                ->label('Scheduled')
                ->disabledOn('edit'),
            ]),
          
          TextInput::make('amount')
            ->label('Amount')
            ->required()
            ->disabled(fn(Get $get) => $get('has_items'))
            ->numeric()
            ->live(onBlur: true)
            ->hint(fn(?string $state) => toIndonesianCurrency($state ?? 0)),

          DatePicker::make('date')
            ->label('Date')
            ->required()
            ->default(Carbon::now())
            ->displayFormat('M d, Y')
            ->closeOnDateSelection()
            ->native(false),

          Textarea::make('name')
            ->label('Notes')
            ->nullable()
            ->columnSpanFull()
            ->required(fn(Get $get) => !$get('has_items'))
            ->rows(3),

          FileUpload::make('attachments')
            ->label('Attachments')
            ->disk('public')
            ->directory('images/payment')
            ->image()
            ->imageEditor()
            ->enableDownload()
            ->enableOpen()
            ->multiple()
            ->columnSpanFull()
        ])
          ->description('Transaction details')
          ->columns(2)
          ->columnSpan(['sm' => 3, 'md' => 2]),

        Section::make([
          TextInput::make('code')
            ->label('Transaction ID')
            ->placeholder('Auto Generated')
            ->disabled()
            ->visibleOn('edit'),

          Select::make('type_id')
            ->label('Type')
            ->options(function (Get $get): Collection {
              if ($get('has_items')) return PaymentType::where('id', 1)->pluck('name', 'id');
              return PaymentType::all()->pluck('name', 'id');
            })
            ->live(onBlur: true)
            ->native(false)
            ->default(1)
            ->required()
            ->disabledOn('edit'),

          Select::make('payment_account_id')
            ->label('Payment')
            ->relationship('payment_account', titleAttribute: 'name')
            ->native(false)
            ->required()
            ->default(PaymentAccount::TUNAI)
            ->disabledOn('edit')
            ->hint(function(?string $state) {
              $payment = PaymentAccount::find($state ?? -1);
              return toIndonesianCurrency($payment->deposit ?? 0);
            })
            ->live(onBlur: true)
            ->afterStateUpdated(function (Set $set, ?string $state, string $operation) {
              $set('payment_account_to_id', null);

              if (!$state)
                return $set('payment_account_deposit', 'Rp. 0');

              $payment_account = PaymentAccount::find($state);

              if ($operation === 'create') {
                $set('payment_account_deposit', toIndonesianCurrency($payment_account->deposit));
              }
            }),

          Select::make('payment_account_to_id')
            ->label('Payment To')
            ->options(function ($get) {
              if (!$get('payment_account_id'))
                return [];
              return PaymentAccount::where('id', '!=', $get('payment_account_id'))
                ->pluck('name', 'id');
            })
            ->native(false)
            ->default(PaymentAccount::DANA)
            ->required(fn($get): bool => ($get('type_id') == 3 || $get('type_id') == 4))
            ->visible(fn($get): bool => ($get('type_id') == 3 || $get('type_id') == 4))
            ->disabled(fn($get, string $operation): bool => !($get('type_id') == 3 || $get('type_id') == 4) || $operation == 'edit')
            ->hint(function(?string $state) {
              $payment = PaymentAccount::find($state ?? -1);
              return toIndonesianCurrency($payment->deposit ?? 0);
            })
            ->live(onBlur: true),
        ])
          ->description('Payment account details')
          ->columns(1)
          ->columnSpan(['sm' => 3, 'md' => 1]),
      ])
      ->columns(3);
  }
}
