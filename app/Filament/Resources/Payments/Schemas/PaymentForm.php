<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Models\PaymentType;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
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
            'default' => 3
          ])
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
                  }
                }),
                
              Toggle::make('is_scheduled')
                ->label('Scheduled')
                ->disabledOn('edit'),

              Toggle::make('is_draft')
                ->label('Draft')
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
            ->multiple()
            ->columnSpanFull(),

          Hidden::make('old_attachments')
            ->label('Old Attachments'),
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
            ->afterStateUpdated(function (Set $set, ?string $state, string $operation) {
              if ($state == PaymentType::WITHDRAWAL) {
                $set('payment_account_id', PaymentAccount::PERMATA_BANK);
                $set('payment_account_to_id', PaymentAccount::TUNAI);

                $paymentAccount = PaymentAccount::find(PaymentAccount::PERMATA_BANK);
                $set('name', 'Tarik Tunai dari ' . $paymentAccount->name);
              }
            }),

          Select::make('payment_account_id')
            ->label('Payment')
            ->options(function (Get $get) {
              if ($get('type_id') == PaymentType::WITHDRAWAL) {
                return PaymentAccount::where('id', '!=', PaymentAccount::TUNAI)
                  ->where('user_id', auth()->user()->id)
                  ->pluck('name', 'id');
              }

              return PaymentAccount::where('id', '!=', $get('payment_account_to_id'))
                ->where('user_id', auth()->user()->id)
                ->pluck('name', 'id');
            })
            ->native(false)
            ->required()
            ->default(PaymentAccount::TUNAI)
            ->hint(function(?string $state) {
              $payment = PaymentAccount::find($state ?? -1);
              return toIndonesianCurrency($payment->deposit ?? 0);
            })
            ->live(onBlur: true)
            ->afterStateUpdated(function (Set $set, Get $get, ?string $state, string $operation) {
              if ($get('type_id') == PaymentType::WITHDRAWAL) {
                $paymentAccount = PaymentAccount::find($state ?? -1);
                $set('name', 'Tarik Tunai dari ' . $paymentAccount?->name);
              }
            }),

          Select::make('payment_account_to_id')
            ->label('Payment To')
            ->options(function (Get $get) {
              if (!$get('payment_account_id')) return [];
              
              if ($get('type_id') == PaymentType::WITHDRAWAL) {
                return PaymentAccount::where('id', PaymentAccount::TUNAI)
                  ->pluck('name', 'id');
              }

              return PaymentAccount::where('id', '!=', $get('payment_account_id'))
                ->where('user_id', auth()->user()->id)
                ->pluck('name', 'id');
            })
            ->native(false)
            ->default(PaymentAccount::DANA)
            ->required(fn($get): bool => ($get('type_id') == 3 || $get('type_id') == 4))
            ->visible(fn($get): bool => ($get('type_id') == 3 || $get('type_id') == 4))
            ->hint(function(?string $state) {
              $payment = PaymentAccount::find($state ?? -1);
              return toIndonesianCurrency($payment->deposit ?? 0);
            })
            ->live(onBlur: true)
            ->afterStateUpdated(function (Set $set, Get $get, ?string $state, string $operation) {
              $paymentAccount = PaymentAccount::find($state ?? -1);
              $type           = $get('type_id');

              if ($type == PaymentType::TRANSFER) {
                $set('name', 'Transfer ke ' . $paymentAccount?->name);
              }
            }),
        ])
          ->description('Payment account details')
          ->columns(1)
          ->columnSpan(['sm' => 3, 'md' => 1]),
      ])
      ->columns(3);
  }
}
