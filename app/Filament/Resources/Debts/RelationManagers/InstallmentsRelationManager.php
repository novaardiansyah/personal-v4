<?php

/*
 * Project Name: personal-v4
 * File: InstallmentsRelationManager.php
 * Created Date: Thursday June 25th 2026
 *
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 *
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

namespace App\Filament\Resources\Debts\RelationManagers;

use App\Filament\Resources\DebtInstallments\Tables\DebtInstallmentsTable;
use App\Models\DebtInstallment;
use App\Models\PaymentAccount;
use App\Models\PaymentType;
use App\Models\Payment;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class InstallmentsRelationManager extends RelationManager
{
	protected static string $relationship = 'installments';

	public function table(Table $table): Table
	{
		$table = DebtInstallmentsTable::configure($table);

		$columns = $table->getColumns();
		unset($columns['debt.name'], $columns['payment.name']);

		return $table
			->columns($columns)
			->defaultSort('installment_number', 'desc')
			->actions([
				ActionGroup::make([
					Action::make('pay')
						->label('Pay')
						->icon('heroicon-o-credit-card')
						->color('success')
						->visible(fn(DebtInstallment $record) => $record->status === 'unpaid')
						->form([
							Select::make('payment_account_id')
								->label('Source Account')
								->options(PaymentAccount::where('user_id', auth()->id())->pluck('name', 'id'))
								->required()
								->native(false),
						])
						->action(function (DebtInstallment $record, array $data): void {
							try {
								$payment = Payment::create([
									'type_id'            => PaymentType::EXPENSE,
									'user_id'            => auth()->id(),
									'payment_account_id' => $data['payment_account_id'],
									'name'               => 'Bayar Cicilan ke-' . $record->installment_number . ': ' . $record->debt->platform_name . ' - ' . $record->debt->name,
									'amount'             => $record->total_amount,
									'date'               => Carbon::now(),
									'is_draft'           => false,
									'is_scheduled'       => false,
								]);

								$record->update([
									'status'     => 'paid',
									'paid_at'    => Carbon::now(),
									'payment_id' => $payment->id,
								]);

								Notification::make()
									->success()
									->title('Payment Recorded')
									->body('The installment payment has been successfully recorded.')
									->send();
							} catch (\Exception $e) {
								Notification::make()
									->danger()
									->title('Payment Failed')
									->body($e->getMessage())
									->send();
							}
						}),
				])
			]);
	}
}
