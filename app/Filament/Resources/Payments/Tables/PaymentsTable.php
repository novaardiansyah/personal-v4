<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Filament\Resources\Payments\Schemas\PaymentAction;
use App\Jobs\PaymentResource\PaymentReportPdf;
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
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
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
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('amount')
          ->label('Nominal')
          ->formatStateUsing(fn (?string $state): string => toIndonesianCurrency($state ?? 0, showCurrency: Setting::showPaymentCurrency()))
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
            PaymentType::INCOME     => 'success',
            PaymentType::EXPENSE    => 'danger',
            PaymentType::TRANSFER   => 'info',
            PaymentType::WITHDRAWAL => 'warning',
            default => 'primary',
          })
          ->formatStateUsing(fn (Payment $record): string => $record->payment_type->name)
          ->toggleable(),
        TextColumn::make('date')
          ->label('Date')
          ->date('M d, Y')
          ->sortable()
          ->toggleable(),
        IconColumn::make('is_scheduled')
          ->label('Scheduled')
          ->boolean()
          ->toggleable(),
        TextColumn::make('name')
          ->label('Notes')
          ->wrap()
          ->words(100)
          ->searchable()
          ->toggleable(),
        ImageColumn::make('attachments')
          ->checkFileExistence(false)
          ->wrap()
          ->limit(3)
          ->toggleable(isToggledHiddenByDefault: true),
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
          ->toggleable(isToggledHiddenByDefault: false),
      ])
      ->defaultSort('updated_at', 'desc')
      ->filters([
        TrashedFilter::make()
          ->native(false),
      ])
      ->headerActions([
        Action::make('print_pdf')
          ->label('PDF')
          ->color('primary')
          ->icon('heroicon-o-printer')
          ->action(function (Action $action): void {
            $livewire = $action->getLivewire();
            $filter   = $livewire->getTableFilterState('date') ?? [];
            
            $params = [
              'start_date' => $filter['from_created_at'] ?? now()->startOfMonth(),
              'end_date'   => $filter['end_created_at'] ?? now()->endOfMonth(),
              'user'       => getUser(),
            ];
            
            PaymentReportPdf::dispatch($params);

            Notification::make()
              ->title('Print PDF in process')
              ->body('PDF print has been processed. You will be notified when the file is ready to download.')
              ->success()
              ->send();
          })
      ])
      ->recordActions([
        ActionGroup::make([
          EditAction::make(),
          
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
