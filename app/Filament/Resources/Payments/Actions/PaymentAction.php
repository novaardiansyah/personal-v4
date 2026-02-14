<?php

/*
 * Project Name: personal-v4
 * File: PaymentAction.php
 * Created Date: Thursday December 11th 2025
 *
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 *
 * Copyright (c) 2025-2026 Nova Ardiansyah, Org
 */

namespace App\Filament\Resources\Payments\Actions;

use App\Jobs\PaymentResource\DailyReportJob;
use App\Jobs\PaymentResource\MonthlyReportJob;
use App\Jobs\PaymentResource\PaymentReportExcelJob;
use App\Jobs\PaymentResource\PaymentReportPdf;
use App\Models\Item;
use App\Models\ItemType;
use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Models\PaymentItem;
use App\Models\PaymentType;
use App\Services\PaymentResource\PaymentService;
use Illuminate\Support\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;

class PaymentAction
{
  // ! ManageDraft
  public static function manageDraftSchema(Schema $schema): Schema
  {
    return $schema
      ->components([
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
      ]);
  }

  public static function manageDraftFillForm(Payment $record): array
  {
    return [
      'amount'                => $record->amount,
      'type_id'               => $record->type_id,
      'payment_account_id'    => $record->payment_account_id,
      'payment_account_to_id' => $record->payment_account_to_id,
      'approve_draft'         => false,
    ];
  }

  public static function manageDraftAction(Action $action, Payment $record, array $data): void
  {
    $record->amount                = intval($data['amount']);
    $record->type_id               = intval($data['type_id']);
    $record->payment_account_id    = intval($data['payment_account_id']);
    $record->payment_account_to_id = $data['payment_account_to_id'] ?? null;

    $record->save();
    $record->load(['payment_account', 'payment_account_to']);

    if ($data['approve_draft']) {
      $mutate = PaymentService::manageDraft($record, false);

      if (!$mutate['status']) {
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
  }
  // ! End ManageDraft

  // ! PrintPdf
  public static function printPdfSchema(Schema $schema): Schema
  {
    return $schema
      ->components([
        Select::make('payment_account_id')
          ->label('Payment')
          ->options(fn() => PaymentAccount::where('user_id', auth()->id())->pluck('name', 'id'))
          ->searchable()
          ->native(false),
        Select::make('report_type')
          ->label('Report Type')
          ->options([
            'date_range' => 'Custom Date Range',
            'daily' => 'Daily Report',
            'monthly' => 'Monthly Report',
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
            $now = Carbon::now();
            $start = $now->copy()->subMonths(12);
            $end = $now->copy()->addMonths(12);
            while ($start->lte($end)) {
              $options[$start->format('Y-m')] = $start->translatedFormat('F Y');
              $start->addMonth();
            }
            return $options;
          })
          ->required()
          ->native(false)
          ->default(Carbon::now()->format('Y-m'))
          ->visible(fn($get) => $get('report_type') === 'monthly'),
        Toggle::make('send_to_email')
          ->label('Send to Email')
          ->default(true),
      ]);
  }

  public static function printPdfAction(Action $action, array $data): void
  {
    $user = getUser();
    $send_to_email = $data['send_to_email'] ?? false;
    $payment_account_id = $data['payment_account_id'] ?? null;

    $sendTo = [
      'send_to_email'      => $send_to_email,
      'user'               => $user,
      'notification'       => true,
      'payment_account_id' => $payment_account_id,
    ];

    match ($data['report_type']) {
      'daily'   => DailyReportJob::dispatch($sendTo),
      'monthly' => MonthlyReportJob::dispatch(array_merge($sendTo, ['periode' => $data['periode']])),
      default   => PaymentReportPdf::dispatch(array_merge($sendTo, [
        'start_date' => $data['start_date'],
        'end_date'   => $data['end_date'],
      ])),
    };

    $messages = [
      'daily'      => 'Daily PDF report will be sent to your email.',
      'monthly'    => 'Monthly PDF report will be sent to your email.',
      'date_range' => 'Custom PDF report will be sent to your email.',
      'default'    => 'You will receive a notification when the PDF report is ready.',
    ];

    if (!$send_to_email) {
      $data['report_type'] = 'default';
    }

    $action->success();
    $action->successNotification(
      Notification::make()
        ->title('PDF report in process')
        ->body($messages[$data['report_type']])
        ->success()
    );
  }
  // ! End PrintPdf

  public static function itemEditAction()
  {
    return EditAction::make()
      ->modalWidth(Width::ThreeExtraLarge)
      ->form(function (Schema $schema, Model $record): Schema {
        return $schema
          ->components([
            TextInput::make('name')
              ->default($record->name)
              ->disabled(),

            Grid::make([
              'sm' => 3,
              'xs' => 1
            ])
              ->schema([
                TextInput::make('amount')
                  ->label('Price')
                  ->required()
                  ->numeric()
                  ->minValue(0)
                  ->default($record->pivot->price)
                  ->live(onBlur: true)
                  ->afterStateUpdated(function ($state, $set, $get): void {
                    $get('quantity') && $set('total', $state * $get('quantity'));
                  })
                  ->hint(fn(?string $state) => toIndonesianCurrency($state ?? 0)),

                TextInput::make('quantity')
                  ->label('Qty')
                  ->required()
                  ->numeric()
                  ->minValue(1)
                  ->default($record->pivot->quantity)
                  ->live(onBlur: true)
                  ->afterStateUpdated(function ($state, $set, $get): void {
                    $get('amount') && $set('total', $state * $get('amount'));
                  })
                  ->hint(fn(?string $state) => number_format($state ?? 0, 0, ',', '.')),

                TextInput::make('total')
                  ->label('Total')
                  ->numeric()
                  ->minValue(0)
                  ->default($record->pivot->total)
                  ->live(onBlur: true)
                  ->readOnly()
                  ->hint(fn(?string $state) => toIndonesianCurrency($state ?? 0)),
              ])
              ->columnSpanFull()
          ])
          ->columns(1);
      })
      ->action(function (array $data, Model $record, RelationManager $livewire, EditAction $action): void {
        PaymentService::updateItemPivot($livewire->getOwnerRecord(), $record, $data);
        $action->getLivewire()->dispatch('refreshForm');
      });
  }

  public static function detachAction()
  {
    return DetachAction::make()
      ->color('danger')
      ->before(function (Model $record, RelationManager $livewire, DetachAction $action): void {
        $owner = $livewire->getOwnerRecord();

        PaymentService::beforeItemDetach($owner, $record, [
          'quantity' => $record->quantity,
          'total'    => $record->pivot_total,
        ]);

        $action->getLivewire()->dispatch('refreshForm');
      });
  }

  public static function printPdf()
  {
    return Action::make('print_pdf')
      ->label('PDF')
      ->color('primary')
      ->icon('heroicon-o-printer')
      ->modalHeading('Generate PDF Report')
      ->modalDescription('Select report type and configure options.')
      ->modalWidth(Width::Medium)
      ->schema(fn(Schema $form): Schema => self::printPdfSchema($form))
      ->action(fn(Action $action, array $data) => self::printPdfAction($action, $data));
  }

  public static function printExcelSchema(Schema $schema): Schema
  {
    return $schema
      ->components([
        Select::make('payment_account_id')
          ->label('Payment')
          ->options(fn() => PaymentAccount::where('user_id', auth()->id())->pluck('name', 'id'))
          ->searchable()
          ->native(false),
        Select::make('report_type')
          ->label('Report Type')
          ->options([
            'date_range' => 'Custom Date Range',
            'monthly' => 'Monthly Report',
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
            $now = Carbon::now();
            $start = $now->copy()->subMonths(12);
            $end = $now->copy()->addMonths(12);
            while ($start->lte($end)) {
              $options[$start->format('Y-m')] = $start->translatedFormat('F Y');
              $start->addMonth();
            }
            return $options;
          })
          ->required()
          ->native(false)
          ->default(Carbon::now()->format('Y-m'))
          ->visible(fn($get) => $get('report_type') === 'monthly'),
        Toggle::make('send_to_email')
          ->label('Send to Email')
          ->default(true),
      ]);
  }

  public static function printExcelAction(Action $action, array $data): void
  {
    $user               = getUser();
    $send_to_email      = $data['send_to_email'] ?? false;
    $payment_account_id = $data['payment_account_id'] ?? null;

    $sendTo = [
      'send_to_email'      => $send_to_email,
      'user'               => $user,
      'notification'       => true,
      'payment_account_id' => $payment_account_id,
    ];

    if ($data['report_type'] === 'monthly') {
      $periode   = $data['periode'];
      $startDate = Carbon::createFromFormat('Y-m', $periode)->startOfMonth()->format('Y-m-d');
      $endDate   = Carbon::createFromFormat('Y-m', $periode)->endOfMonth()->format('Y-m-d');
    } else {
      $startDate = $data['start_date'];
      $endDate   = $data['end_date'];
    }

    PaymentReportExcelJob::dispatch(array_merge($sendTo, [
      'start_date' => $startDate,
      'end_date'   => $endDate,
    ]));

    $messages = [
      'monthly'    => 'Monthly Excel report will be sent to your email.',
      'date_range' => 'Custom Excel report will be sent to your email.',
      'default'    => 'You will receive a notification when the Excel report is ready.',
    ];

    if (!$send_to_email) {
      $data['report_type'] = 'default';
    }

    $action->success();
    $action->successNotification(
      Notification::make()
        ->title('Excel Report in process')
        ->body($messages[$data['report_type']])
        ->success()
    );
  }

  public static function printExcel()
  {
    return Action::make('print_excel')
      ->label('Excel')
      ->color('primary')
      ->icon('heroicon-o-printer')
      ->modalHeading('Generate Excel Report')
      ->modalDescription('Select report type and configure options.')
      ->modalWidth(Width::Medium)
      ->schema(fn(Schema $form): Schema => self::printExcelSchema($form))
      ->action(fn(Action $action, array $data) => self::printExcelAction($action, $data));
  }

	public static function set_owner_price(PaymentItem $record)
	{
		$owner = $record->payment;
		$item = $record->item;

		$item->update([
			'amount'     => $record->price,
			'updated_at' => now()
		]);

		$expense = $owner->amount + (int) $record->total;
		$note    = trim(($owner->name ?? '') . ', ' . "{$item->name} (x{$record->quantity})", ', ');

		$owner->update([
			'amount' => $expense,
			'name'   => $note
		]);
	}
}
