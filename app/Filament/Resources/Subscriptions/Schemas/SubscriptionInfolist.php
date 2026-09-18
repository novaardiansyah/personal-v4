<?php

namespace App\Filament\Resources\Subscriptions\Schemas;

use App\Models\Setting;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SubscriptionInfolist
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make('')
          ->description('Subscription Information')
          ->schema([
            TextEntry::make('code')
              ->label('Subscription ID')
              ->copyable()
              ->badge()
              ->color('info'),

            TextEntry::make('name')
              ->label('Name')
              ->placeholder('N/A'),

            TextEntry::make('amount')
              ->label('Amount')
              ->formatStateUsing(fn(?string $state): string => toIndonesianCurrency($state ?? 0, showCurrency: Setting::showPaymentCurrency())),

            TextEntry::make('cycle')
              ->label('Cycle')
              ->badge()
              ->color('primary'),

            TextEntry::make('next_date')
              ->label('Next Date')
              ->date('M d, Y'),

            TextEntry::make('reminder_days_before')
              ->label('Reminder')
              ->formatStateUsing(fn($state) => "{$state} days before"),

            TextEntry::make('is_paused')
              ->label('Status')
              ->badge()
              ->formatStateUsing(fn(bool $state): string => $state ? 'Paused' : 'Active')
              ->color(fn(bool $state): string => $state ? 'warning' : 'success'),

            TextEntry::make('category.name')
              ->label('Category')
              ->placeholder('N/A'),

            TextEntry::make('payment_account.name')
              ->label('Payment Account')
              ->placeholder('N/A'),

            TextEntry::make('last_reminded_at')
              ->label('Last Reminded At')
              ->dateTime('M d, Y H:i')
              ->sinceTooltip()
              ->placeholder('Never'),
          ])
          ->columns(['xl' => 3, '2xl' => 4])
          ->columnSpan(['sm' => 3, 'md' => 2]),

        Section::make('')
          ->description('System Information')
          ->schema([
            TextEntry::make('created_at')
              ->label('Created At')
              ->dateTime()
              ->sinceTooltip(),

            TextEntry::make('updated_at')
              ->label('Last Updated')
              ->dateTime()
              ->sinceTooltip(),

            TextEntry::make('deleted_at')
              ->label('Deleted At')
              ->dateTime()
              ->sinceTooltip()
              ->placeholder('Active'),
          ])
          ->columns(1)
          ->columnSpan(['sm' => 3, 'md' => 1]),
      ])
      ->columns(3);
  }
}
