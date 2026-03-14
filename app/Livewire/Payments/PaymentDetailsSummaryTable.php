<?php

/*
 * Project Name: personal-v4
 * File: PaymentDetailsSummaryTable.php
 * Created Date: Sunday March 15th 2026
 * 
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 * 
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

namespace App\Livewire\Payments;

use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\Setting;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\TableComponent;

class PaymentDetailsSummaryTable extends TableComponent
{
	public string $ids = '';

	public function table(Table $table): Table
	{
		$recordIds = array_filter(explode(',', $this->ids));

		$records = Payment::with(['payment_type'])
			->whereIn('id', $recordIds)
			->get();

		$grouped = [];

		foreach ($records as $record) {
			$typeName = $record->payment_type->name ?? 'Unknown';
			$typeId = $record->type_id;

			if (!isset($grouped[$typeId])) {
				$grouped[$typeId] = [
					'id'    => $typeId,
					'name'  => $typeName,
					'count' => 0,
					'total' => 0,
				];
			}

			$grouped[$typeId]['count']++;
			$grouped[$typeId]['total'] += $record->amount;
		}

		$showCurrency = Setting::showPaymentCurrency();

		return $table
			->records(fn(): array => $grouped)
			->columns([
				TextColumn::make('name')
					->label('Type')
					->badge()
					->color(fn($record) => match ((int) $record['id']) {
						PaymentType::INCOME     => 'success',
						PaymentType::EXPENSE    => 'danger',
						PaymentType::TRANSFER   => 'info',
						PaymentType::WITHDRAWAL => 'warning',
						default => 'primary',
					}),
				TextColumn::make('count')
					->label('Transactions')
					->alignCenter()
					->formatStateUsing(fn($state): string => 'x' . $state),
				TextColumn::make('total')
					->label('Total')
					->alignEnd()
					->formatStateUsing(fn($state): string => toIndonesianCurrency($state ?? 0, showCurrency: $showCurrency)),
			])
			->paginated(false);
	}

	public function render()
	{
		return view('livewire.payments.payment-details-summary-table');
	}
}
