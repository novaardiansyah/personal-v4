<?php

/*
 * Project Name: personal-v4
 * File: PaymentsTable.php
 * Created Date: Thursday December 11th 2025
 * 
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 * 
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

namespace App\Filament\Resources\Payments\Tables;

use App\Filament\Resources\Payments\Actions\PaymentAction;
use App\Filament\Resources\Payments\Schemas\PaymentFilter;
use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\Setting;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
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
				TextColumn::make('category.name')
					->label('Category')
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
					->toggleable(),
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
					->searchable()
					->preload(true)
					->native(false),
				PaymentFilter::paymentAccount(),
				PaymentFilter::paymentAccountTo(),
				PaymentFilter::category(),
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
					PaymentAction::manageDraft(),
					DeleteAction::make(),
					RestoreAction::make(),
				])
			])
			->toolbarActions([
				BulkActionGroup::make([
					PaymentAction::detailsBulk(),
					RestoreBulkAction::make(),
				]),
			]);
	}
}
