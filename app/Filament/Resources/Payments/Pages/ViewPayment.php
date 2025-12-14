<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\Setting;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewPayment extends ViewRecord
{
  protected static string $resource = PaymentResource::class;

  protected function getHeaderActions(): array
  {
    return [
      EditAction::make(),
    ];
  }

  public function infolist(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make('Transaction Information')
          ->icon('heroicon-o-banknotes')
          ->schema([
            Grid::make(2)
              ->schema([
                TextEntry::make('code')
                  ->label('Transaction ID')
                  ->copyable()
                  ->badge()
                  ->color('gray'),
                TextEntry::make('type_id')
                  ->label('Type')
                  ->badge()
                  ->color(fn(string $state): string => match ((int) $state) {
                    PaymentType::INCOME => 'success',
                    PaymentType::EXPENSE => 'danger',
                    PaymentType::TRANSFER => 'info',
                    PaymentType::WITHDRAWAL => 'warning',
                    default => 'primary',
                  })
                  ->formatStateUsing(fn(Payment $record): string => $record->payment_type->name),
              ]),
            Grid::make(2)
              ->schema([
                TextEntry::make('amount')
                  ->label('Nominal')
                  ->formatStateUsing(fn(?string $state): string => toIndonesianCurrency($state ?? 0, showCurrency: Setting::showPaymentCurrency()))
                  ->weight('bold'),
                TextEntry::make('date')
                  ->label('Date')
                  ->date('M d, Y')
                  ->icon('heroicon-o-calendar'),
              ]),
            TextEntry::make('name')
              ->label('Notes')
              ->columnSpanFull()
              ->markdown()
              ->prose()
              ->placeholder('No notes available'),
          ]),

        Section::make('Payment Account')
          ->icon('heroicon-o-credit-card')
          ->schema([
            Grid::make(2)
              ->schema([
                TextEntry::make('payment_account.name')
                  ->label('From Account')
                  ->icon('heroicon-o-wallet')
                  ->placeholder('N/A'),
                TextEntry::make('payment_account_to.name')
                  ->label('To Account')
                  ->icon('heroicon-o-arrow-right-circle')
                  ->placeholder('N/A')
                  ->visible(fn(Payment $record): bool => in_array((int) $record->type_id, [PaymentType::TRANSFER, PaymentType::WITHDRAWAL])),
              ]),
          ]),

        Section::make('Status')
          ->icon('heroicon-o-flag')
          ->schema([
            Grid::make(3)
              ->schema([
                IconEntry::make('is_scheduled')
                  ->label('Scheduled')
                  ->boolean(),
                IconEntry::make('has_items')
                  ->label('Has Items')
                  ->boolean(),
                IconEntry::make('is_draft')
                  ->label('Draft')
                  ->boolean(),
              ]),
          ]),

        Section::make('Record Information')
          ->icon('heroicon-o-clock')
          ->schema([
            Grid::make(3)
              ->schema([
                TextEntry::make('created_at')
                  ->label('Created')
                  ->dateTime(),
                TextEntry::make('updated_at')
                  ->label('Last Updated')
                  ->dateTime()
                  ->sinceTooltip(),
                TextEntry::make('deleted_at')
                  ->label('Deleted')
                  ->dateTime(),
              ]),
          ]),
        
        Section::make('Attachments')
          ->icon('heroicon-o-paper-clip')
          ->schema([
            ImageEntry::make('attachments')
              ->checkFileExistence(false)
              ->stacked()
              ->imageWidth(100)
              ->imageHeight('100%')
              ->columnSpanFull(),
          ])
          ->visible(fn(Payment $record): bool => !empty($record->attachments))
          ->collapsible(),
      ]);
  }
}
