<?php

namespace App\Filament\Resources\Subscriptions\RelationManagers;

use App\Models\Setting;
use App\Models\SubscriptionPayment;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
  protected static string $relationship = 'subscription_payments';

  protected static ?string $title = 'Payment History';

  public function table(Table $table): Table
  {
    return $table
      ->recordTitleAttribute('period')
      ->columns([
        TextColumn::make('index')
          ->rowIndex()
          ->label('#'),

        TextColumn::make('period')
          ->label('Period')
          ->badge()
          ->color('info')
          ->sortable(),

        TextColumn::make('payment.code')
          ->label('Transaction ID')
          ->searchable()
          ->copyable()
          ->badge(),

        TextColumn::make('payment.name')
          ->label('Notes')
          ->wrap()
          ->searchable(),

        TextColumn::make('payment.amount')
          ->label('Amount')
          ->formatStateUsing(fn(?string $state): string => toIndonesianCurrency($state ?? 0, showCurrency: Setting::showPaymentCurrency())),

        TextColumn::make('payment.date')
          ->label('Date')
          ->date('M d, Y'),

        TextColumn::make('payment.is_draft')
          ->label('Status')
          ->badge()
          ->formatStateUsing(fn(bool $state): string => $state ? 'Draft' : 'Paid')
          ->color(fn(bool $state): string => $state ? 'warning' : 'success'),

        TextColumn::make('payment.payment_account.name')
          ->label('Account')
          ->placeholder('N/A'),

        TextColumn::make('created_at')
          ->label('Drafted At')
          ->dateTime('M d, Y H:i')
          ->sinceTooltip()
          ->sortable(),
      ])
      ->defaultSort('created_at', 'desc')
      ->recordActions([
        ActionGroup::make([
          Action::make('view_payment')
            ->label('View Payment')
            ->icon(Heroicon::OutlinedEye)
            ->color('info')
            ->url(fn(SubscriptionPayment $record): ?string => $record->payment_id ? url("/admin/payments/{$record->payment_id}") : null)
            ->openUrlInNewTab(),
          DeleteAction::make(),
        ]),
      ]);
  }
}
