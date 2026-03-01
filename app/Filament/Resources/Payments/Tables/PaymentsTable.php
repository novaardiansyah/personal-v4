<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Filament\Resources\Payments\Actions\PaymentAction;
use App\Filament\Resources\Payments\Schemas\PaymentFilter;
use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class PaymentsTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('index')
          ->rowIndex()
          ->label('#'),
        TextColumn::make('code')
          ->label('Transaction ID')
          ->searchable()
          ->copyable()
					->badge()
          ->toggleable(),
        TextColumn::make('amount')
          ->label('Nominal')
          ->formatStateUsing(fn(?string $state): string => toIndonesianCurrency($state ?? 0, showCurrency: Setting::showPaymentCurrency()))
          ->toggleable(),
        TextColumn::make('name')
          ->label('Notes')
          ->wrap()
          ->words(100)
          ->searchable()
          ->toggleable(),
        TextColumn::make('payment_account.name')
          ->label('Payment')
          ->toggleable(),
        TextColumn::make('payment_account_to.name')
          ->label('Payment To')
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('type_id')
          ->label('Type')
          ->badge()
          ->color(fn(string $state): string => match ((int) $state) {
            PaymentType::INCOME => 'success',
            PaymentType::EXPENSE => 'danger',
            PaymentType::TRANSFER => 'info',
            PaymentType::WITHDRAWAL => 'warning',
            default => 'primary',
          })
          ->formatStateUsing(fn(Payment $record): string => $record->payment_type->name)
          ->toggleable(),
        IconColumn::make('is_scheduled')
          ->label('Scheduled')
          ->boolean()
					->toggleable(isToggledHiddenByDefault: true),
        IconColumn::make('is_draft')
          ->label('Draft')
          ->boolean()
					->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('date')
          ->label('Date')
          ->date('M d, Y')
          ->sortable()
          ->toggleable(),
        TextColumn::make('deleted_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('created_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('updated_at')
          ->dateTime()
          ->sortable()
          ->sinceTooltip()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
			->recordAction(null)
			->recordUrl(null)
      ->defaultSort('date', 'desc')
      ->filters([
        TrashedFilter::make()
          ->native(false),

        PaymentFilter::date(),
      ])
      ->headerActions([
        PaymentAction::printExcel(),
        PaymentAction::printPdf(),
      ])
      ->recordActions([
        ActionGroup::make([
          ViewAction::make(),

          EditAction::make(),

          Action::make('manage_draft')
            ->label('Kelola Draft')
            ->color('success')
            ->icon('heroicon-o-document-text')
            ->visible(fn(Payment $record): bool => $record->is_draft === true)
            ->modalHeading('Kelola Draft')
            ->modalDescription('Edit transaksi draft dan tentukan statusnya.')
            ->modalWidth(Width::Large)
            ->schema(fn(Schema $form): Schema => PaymentAction::manageDraftSchema($form))
            ->fillForm(fn(Payment $record): array => PaymentAction::manageDraftFillForm($record))
            ->action(fn(Action $action, Payment $record, array $data) => PaymentAction::manageDraftAction($action, $record, $data)),

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
