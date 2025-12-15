<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Filament\Resources\Payments\Schemas\PaymentAction;
use App\Jobs\PaymentResource\DailyReportJob;
use App\Jobs\PaymentResource\MonthlyReportJob;
use App\Jobs\PaymentResource\PaymentReportPdf;
use App\Models\Payment;
use App\Models\PaymentAccount;
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
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
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
            ->schema(fn(Schema $form): Schema => $form->components([
              TextInput::make('amount')
                ->label('Amount')
                ->required()
                ->numeric()
                ->live(onBlur: true)
                ->hint(fn(?string $state) => toIndonesianCurrency($state ?? 0)),

              Select::make('type_id')
                ->label('Type')
                ->options(PaymentType::all()->pluck('name', 'id'))
                ->required()
                ->native(false)
                ->live(),

              Select::make('payment_account_id')
                ->label('Payment Account')
                ->options(fn(Get $get) => PaymentAccount::where('id', '!=', $get('payment_account_to_id'))
                  ->where('user_id', auth()->id())
                  ->pluck('name', 'id'))
                ->required()
                ->native(false)
                ->live()
                ->hint(fn(?string $state) => toIndonesianCurrency(PaymentAccount::find($state ?? -1)?->deposit ?? 0)),

              Select::make('payment_account_to_id')
                ->label('Payment To')
                ->options(fn(Get $get) => PaymentAccount::where('id', '!=', $get('payment_account_id'))
                  ->where('user_id', auth()->id())
                  ->pluck('name', 'id'))
                ->required(fn(Get $get): bool => in_array($get('type_id'), [PaymentType::TRANSFER, PaymentType::WITHDRAWAL]))
                ->visible(fn(Get $get): bool => in_array($get('type_id'), [PaymentType::TRANSFER, PaymentType::WITHDRAWAL]))
                ->native(false)
                ->hint(fn(?string $state) => toIndonesianCurrency(PaymentAccount::find($state ?? -1)?->deposit ?? 0)),

              Toggle::make('approve_draft')
                ->label('Approve Draft')
                ->helperText('Jika draft disetujui, transaksi akan dijalankan dan saldo akan dimutasi.')
                ->default(false),
            ]))
            ->fillForm(fn(Payment $record): array => [
              'amount' => $record->amount,
              'type_id' => $record->type_id,
              'payment_account_id' => $record->payment_account_id,
              'payment_account_to_id' => $record->payment_account_to_id,
              'approve_draft' => false,
            ])
            ->action(function (Action $action, Payment $record, array $data): void {
              $record->amount = intval($data['amount']);
              $record->type_id = intval($data['type_id']);
              $record->payment_account_id = intval($data['payment_account_id']);
              $record->payment_account_to_id = $data['payment_account_to_id'] ?? null;

              if ($data['approve_draft']) {
                $record->is_draft = false;
              }

              $record->save();
              $record->load(['payment_account', 'payment_account_to']);

              if ($data['approve_draft']) {
                $mutate = Payment::approveDraft($record);

                if (!$mutate['status']) {
                  $record->is_draft = true;
                  $record->save();

                  Notification::make()
                    ->danger()
                    ->title('Transaction Failed!')
                    ->body($mutate['message'] ?? 'Something went wrong!')
                    ->send();

                  $action->halt();
                  return;
                }
              }

              Notification::make()
                ->success()
                ->title($data['approve_draft'] ? 'Draft Approved!' : 'Draft Updated!')
                ->body($data['approve_draft'] ? 'Draft has been approved and balance has been mutated.' : 'Draft has been updated successfully.')
                ->send();
            }),

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
