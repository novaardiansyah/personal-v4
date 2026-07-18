<?php

namespace App\Filament\Resources\Subscriptions\Tables;

use App\Filament\Resources\Subscriptions\Actions\PauseResumeAction;
use App\Filament\Resources\Subscriptions\Actions\MarkAsPaidAction;
use App\Filament\Resources\Subscriptions\Filters\SubscriptionsFilter;
use App\Models\Setting;
use App\Models\Subscription;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class SubscriptionsTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('index')
          ->rowIndex()
          ->label('#'),

        TextColumn::make('code')
          ->label('Subscription ID')
          ->searchable()
          ->copyable()
          ->badge()
          ->toggleable(),

        TextColumn::make('name')
          ->label('Name')
          ->wrap()
          ->searchable()
          ->toggleable(),

        TextColumn::make('amount')
          ->label('Amount')
          ->formatStateUsing(fn(?string $state): string => toIndonesianCurrency($state ?? 0, showCurrency: Setting::showPaymentCurrency()))
          ->toggleable(),

        TextColumn::make('next_date')
          ->label('Next Date')
          ->date('M d, Y')
          ->toggleable(),

        TextColumn::make('cycle')
          ->label('Cycle')
          ->badge()
          ->toggleable(),

        TextColumn::make('category.name')
          ->label('Category')
          ->toggleable(),

        TextColumn::make('payment_account.name')
          ->label('Payment Account')
          ->toggleable(),

        IconColumn::make('is_paused')
          ->label('Paused')
          ->boolean()
          ->toggleable(),

        TextColumn::make('updated_at')
          ->label('Updated')
          ->sinceTooltip()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->recordAction(null)
      ->recordUrl(null)
      ->defaultSort('next_date', 'asc')
      ->filters([
        TrashedFilter::make()
          ->searchable()
          ->preload(true)
          ->native(false),
        SubscriptionsFilter::status(),
      ])
      ->recordActions([
        ActionGroup::make([
          EditAction::make(),
          PauseResumeAction::make(),
          MarkAsPaidAction::make(),
          DeleteAction::make(),
          RestoreAction::make(),
        ])
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          RestoreBulkAction::make(),
        ]),
      ]);
  }
}