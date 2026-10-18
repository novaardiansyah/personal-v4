<?php

/*
 * Project Name: personal-v4
 * File: DebtForm.php
 * Created Date: Thursday June 25th 2026
 * 
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 * 
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

namespace App\Filament\Resources\Debts\Schemas;

use App\Models\PaymentAccount;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class DebtForm
{
	public static function configure(Schema $schema): Schema
	{
		return $schema
			->components([
				Section::make([
					Grid::make([
						'sm' => 2,
						'xs' => 1
					])
						->columnSpanFull()
						->schema([
							TextInput::make('platform_name')
								->required(),
							TextInput::make('name')
								->required(),
							TextInput::make('principal_amount')
								->required()
								->numeric()
								->live(onBlur: true)
								->hint(fn(?string $state) => toIndonesianCurrency((float) ($state ?? 0)))
								->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
									$principal = (float) ($state ?? 0);
									$totalPayable = (float) ($get('total_payable_amount') ?? 0);
									if ($principal > 0 && $totalPayable >= $principal) {
										$rate = (($totalPayable - $principal) / $principal) * 100;
										$set('interest_rate', round($rate, 2));
									}
								}),
							TextInput::make('total_payable_amount')
								->label('Total Payable')
								->required()
								->numeric()
								->live(onBlur: true)
								->hint(fn(?string $state) => toIndonesianCurrency((float) ($state ?? 0)))
								->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
									$totalPayable = (float) ($state ?? 0);
									$principal = (float) ($get('principal_amount') ?? 0);
									if ($principal > 0 && $totalPayable >= $principal) {
										$rate = (($totalPayable - $principal) / $principal) * 100;
										$set('interest_rate', round($rate, 2));
									}
								}),
							TextInput::make('interest_rate')
								->label('Interest Rate')
								->required()
								->numeric()
								->default(0)
								->readOnly()
								->suffix('%'),
							TextInput::make('tenor')
								->required()
								->numeric()
								->default(1),
							DatePicker::make('start_date')
								->required()
								->default(now())
								->native(false),
						])
				])
					->description('Debt Information')
					->columnSpan(['sm' => 3, 'md' => 2]),

				Section::make([
					TextInput::make('code')
						->label('Debt ID')
						->placeholder('Auto Generated')
						->disabled()
						->visibleOn('edit'),
					Select::make('payment_account_id')
						->label('Disbursement Account')
						->relationship('payment_account', 'name', fn($query) => $query->where('user_id', auth()->id()))
						->native(false)
						->preload()
						->searchable()
						->required()
						->live(onBlur: true)
						->hint(fn(?string $state) => toIndonesianCurrency(PaymentAccount::find($state)?->deposit ?? 0)),
					Select::make('status')
						->options([
							'ongoing' => 'Ongoing',
							'partial_payment' => 'Partial Payment',
							'paid' => 'Paid',
						])
						->native(false)
						->preload()
						->default('ongoing')
						->required()
						->live(),
					TextInput::make('paid_tenor')
						->label('Paid Installments')
						->numeric()
						->default(0)
						->visibleOn('create')
						->visible(fn(Get $get) => $get('status') === 'partial_payment')
						->required(fn(Get $get) => $get('status') === 'partial_payment')
						->rules([
							fn(Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
								$tenor = (int) $get('tenor');
								if ((int) $value >= $tenor) {
									$fail("Paid installments must be less than the total tenor ({$tenor}).");
								}
							},
						]),
					Textarea::make('description')
						->rows(3),
				])
					->description('Details')
					->columns(1)
					->columnSpan(['sm' => 3, 'md' => 1])
			])
			->columns(3);
	}
}
