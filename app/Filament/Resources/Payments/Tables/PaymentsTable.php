<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Filament\Resources\Payments\Schemas\PaymentAction;
use App\Jobs\PaymentResource\DailyReportJob;
use App\Jobs\PaymentResource\MonthlyReportJob;
use App\Jobs\PaymentResource\PaymentReportPdf;
use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\Setting;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
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
          ->toggleable(),
        IconColumn::make('is_draft')
          ->label('Draft')
          ->boolean()
          ->toggleable(),
        TextColumn::make('date')
          ->label('Date')
          ->date('M d, Y')
          ->sortable()
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
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->defaultSort('updated_at', 'desc')
      ->filters([
        TrashedFilter::make()
          ->native(false),
      ])
      ->headerActions([
        Action::make('print_pdf')
          ->label('Report')
          ->color('primary')
          ->icon('heroicon-o-printer')
          ->modalHeading('Generate Payment Report')
          ->modalDescription('Select report type and configure options.')
          ->modalWidth(Width::Medium)
          ->schema([
            Select::make('report_type')
              ->label('Report Type')
              ->options([
                'date_range' => 'Custom Date Range (PDF)',
                'daily' => 'Daily Report (Email)',
                'monthly' => 'Monthly Report (Email)',
              ])
              ->default('monthly')
              ->required()
              ->live()
              ->native(false),
            DatePicker::make('start_date')
              ->label('Start Date')
              ->required()
              ->native(false)
              ->default(Carbon::now()->startOfMonth())
              ->visible(fn($get) => $get('report_type') === 'date_range'),
            DatePicker::make('end_date')
              ->label('End Date')
              ->required()
              ->native(false)
              ->default(Carbon::now()->endOfMonth())
              ->visible(fn($get) => $get('report_type') === 'date_range'),
            Select::make('periode')
              ->label('Periode (Month)')
              ->options(function () {
                $options = [];
                $year = Carbon::now()->year;
                for ($month = 1; $month <= 12; $month++) {
                  $date = Carbon::createFromDate($year, $month, 1);
                  $options[$date->format('Y-m')] = $date->translatedFormat('F Y');
                }
                return $options;
              })
              ->required()
              ->native(false)
              ->default(Carbon::now()->format('Y-m'))
              ->visible(fn($get) => $get('report_type') === 'monthly'),
          ])
          ->action(function (Action $action, array $data): void {
            $user = getUser();

            match ($data['report_type']) {
              'daily' => DailyReportJob::dispatch(),
              'monthly' => MonthlyReportJob::dispatch([
                'periode' => $data['periode'],
                'user' => $user,
              ]),
              default => PaymentReportPdf::dispatch([
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'user' => $user,
              ]),
            };

            $messages = [
              'daily' => 'Daily report will be sent to your email.',
              'monthly' => 'Monthly report will be sent to your email.',
              'date_range' => 'Custom report will be sent to your email.',
            ];

            Notification::make()
              ->title('Report in process')
              ->body($messages[$data['report_type']] ?? 'Report is being processed.')
              ->success()
              ->send();
          })
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
