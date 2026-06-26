<?php

/*
 * Project Name: personal-v4
 * File: DebtInfolist.php
 * Created Date: Thursday June 25th 2026
 * 
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 * 
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

namespace App\Filament\Resources\Debts\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DebtInfolist
{
	public static function configure(Schema $schema): Schema
	{
		return $schema
			->components([
				Section::make('')
					->description('Debt Information')
					->schema([
						TextEntry::make('code')
							->label('Debt ID')
							->copyable()
							->badge()
							->color('info'),
						TextEntry::make('platform_name')
							->label('Platform')
							->placeholder('N/A'),
						TextEntry::make('name')
							->label('Name')
							->placeholder('N/A'),
						TextEntry::make('status')
							->label('Status')
							->badge()
							->color(fn(string $state): string => match ($state) {
								'paid' => 'success',
								'ongoing' => 'warning',
								default => 'primary'
							}),
						TextEntry::make('principal_amount')
							->label('Principal Amount')
							->formatStateUsing(fn(?string $state) => toIndonesianCurrency((float) ($state ?? 0)))
							->weight('bold'),
						TextEntry::make('admin_fee')
							->label('Admin Fee')
							->formatStateUsing(fn(?string $state) => toIndonesianCurrency((float) ($state ?? 0))),
						TextEntry::make('disbursement_amount')
							->label('Net Disbursement')
							->formatStateUsing(fn(?string $state) => toIndonesianCurrency((float) ($state ?? 0))),
						TextEntry::make('interest_rate')
							->label('Interest Rate')
							->suffix('%'),
						TextEntry::make('service_fee_rate')
							->label('Service Fee Rate')
							->suffix('%'),
						TextEntry::make('tenor')
							->label('Tenor')
							->formatStateUsing(fn($state) => $state . ' Months'),
						TextEntry::make('start_date')
							->label('Start Date')
							->date('M d, Y'),
						TextEntry::make('payment_account.name')
							->label('Disbursement Account')
							->placeholder('N/A'),
						TextEntry::make('description')
							->label('Description')
							->placeholder('No description available')
							->columnSpanFull(),
					])
					->columns(['xl' => 3, '2xl' => 4])
					->columnSpan(['sm' => 3, 'md' => 2]),

				Section::make('')
					->description('System Information')
					->schema([
						TextEntry::make('created_at')
							->label('Created At')
							->dateTime(),
						TextEntry::make('updated_at')
							->label('Last Updated')
							->dateTime()
							->sinceTooltip(),
						TextEntry::make('deleted_at')
							->label('Deleted At')
							->dateTime()
							->placeholder('Active'),
					])
					->columns(1)
					->columnSpan(['sm' => 3, 'md' => 1]),
			])
			->columns(3);
	}
}
