<?php

namespace App\Filament\Resources\Subscriptions\Schemas;

use App\Models\PaymentAccount;
use App\Models\PaymentCategory;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

class SubscriptionForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make([
          TextInput::make('name')
            ->label('Name')
            ->required()
            ->columnSpanFull(),

          TextInput::make('amount')
            ->label('Amount')
            ->required()
            ->numeric()
            ->live(onBlur: true)
            ->hint(fn(?string $state) => toIndonesianCurrency($state ?? 0)),

          Select::make('payment_account_id')
            ->label('Payment Account')
            ->relationship('payment_account', 'name')
            ->native(false)
            ->preload()
            ->searchable()
            ->required()
            ->live(onBlur: true)
            ->hint(fn(?string $state) => toIndonesianCurrency(PaymentAccount::find($state)?->deposit ?? 0)),

          Select::make('category_id')
            ->label('Category')
            ->relationship('category', 'name', fn ($query) => $query->where('user_id', auth()->id())->orderBy('updated_at', 'desc'))
            ->native(false)
            ->preload()
            ->searchable()
            ->nullable()
            ->default(fn () => PaymentCategory::where('user_id', auth()->id())->where('is_default', true)->first()?->id),

          Select::make('cycle')
            ->label('Cycle')
            ->options([
              'monthly'   => 'Monthly',
              'quarterly' => 'Quarterly',
              'yearly'    => 'Yearly',
            ])
            ->required()
            ->native(false),

          DatePicker::make('next_date')
            ->label('Next Date')
            ->required()
            ->displayFormat('M d, Y')
            ->closeOnDateSelection()
            ->native(false),

          TextInput::make('reminder_days_before')
            ->label('Reminder (days before)')
            ->required()
            ->numeric()
            ->minValue(0)
            ->default(3),

          Toggle::make('is_paused')
            ->label('Paused')
            ->default(false),
        ])
          ->description('Subscription information')
          ->columns(2)
          ->columnSpanFull()
          ->collapsible(),
      ])
      ->columns(2);
  }
}