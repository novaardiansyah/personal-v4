<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Models\PaymentAccount;
use App\Models\PaymentType;
use Illuminate\Support\Carbon;

use Filament\Forms\Components\DatePicker;
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
            'sm' => 3,
            'xs' => 1
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
        ])
          ->description('Transaction details')
          ->columns(2)
          ->columnSpan(['sm' => 3, 'md' => 2])
          ->collapsible(),

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
            ->relationship('payment_account', 'name')
						->getOptionLabelFromRecordUsing(function(PaymentAccount $record): string {
							return $record->name . ' (' . toIndonesianCurrency($record->deposit) . ')';
						})
            ->native(false)
						->preload()
						->searchable()
            ->required()
            ->default(PaymentAccount::TUNAI),

          Select::make('payment_account_to_id')
            ->label('Payment To')
            ->relationship('payment_account_to', 'name')
            ->getOptionLabelFromRecordUsing(function(PaymentAccount $record): string {
							return $record->name . ' (' . toIndonesianCurrency($record->deposit) . ')';
						})
            ->native(false)
						->preload()
						->searchable()
            ->required(fn(Get $get): bool => ($get('type_id') == PaymentType::TRANSFER || $get('type_id') == PaymentType::WITHDRAWAL))
            ->visible(fn(Get $get): bool => ($get('type_id') == PaymentType::TRANSFER || $get('type_id') == PaymentType::WITHDRAWAL)),
        ])
          ->description('Payment account details')
          ->columns(1)
          ->columnSpan(['sm' => 3, 'md' => 1])
          ->collapsible(),
      ])
      ->columns(3);
  }
}
