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
									$principal = (float) $state;
									$adminFee = (float) $get('admin_fee');
									$set('disbursement_amount', $principal - $adminFee);
								}),
							TextInput::make('admin_fee')
								->required()
								->numeric()
								->default(0)
								->live(onBlur: true)
								->hint(fn(?string $state) => toIndonesianCurrency((float) ($state ?? 0)))
								->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
									$principal = (float) $get('principal_amount');
									$adminFee = (float) $state;
									$set('disbursement_amount', $principal - $adminFee);
								}),
							TextInput::make('disbursement_amount')
								->required()
								->numeric()
								->readOnly()
								->hint(fn(?string $state) => toIndonesianCurrency((float) ($state ?? 0))),
							TextInput::make('interest_rate')
								->required()
								->numeric()
								->default(0)
								->suffix('%'),
							TextInput::make('service_fee_rate')
								->required()
								->numeric()
								->default(0)
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
							'paid' => 'Paid',
						])
						->native(false)
						->preload()
						->default('ongoing')
						->required(),
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
