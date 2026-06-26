<?php

/*
 * Project Name: personal-v4
 * File: DebtsTable.php
 * Created Date: Thursday June 25th 2026
 * 
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 * 
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

namespace App\Filament\Resources\Debts\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class DebtsTable
{
	public static function configure(Table $table): Table
	{
		return $table
			->columns([
				TextColumn::make('index')
					->rowIndex()
					->label('#'),
				TextColumn::make('code')
					->label('Code')
					->searchable()
					->sortable()
					->copyable()
					->badge(),
				TextColumn::make('platform_name')
					->label('Platform')
					->sortable()
					->toggleable(isToggledHiddenByDefault: true),
				TextColumn::make('name')
					->label('Name')
					->searchable()
					->sortable()
					->toggleable(),
				TextColumn::make('principal_amount')
					->label('Principal')
					->sortable()
					->toggleable()
					->formatStateUsing(fn($state) => toIndonesianCurrency($state)),
				TextColumn::make('admin_fee')
					->label('Admin Fee')
					->sortable()
					->toggleable(isToggledHiddenByDefault: true)
					->formatStateUsing(fn($state) => toIndonesianCurrency($state)),
				TextColumn::make('disbursement_amount')
					->label('Net Disbursement')
					->sortable()
					->toggleable(isToggledHiddenByDefault: true)
					->formatStateUsing(fn($state) => toIndonesianCurrency($state)),
				TextColumn::make('interest_rate')
					->label('Interest Rate')
					->sortable()
					->toggleable(isToggledHiddenByDefault: true)
					->formatStateUsing(fn($state) => number_format($state, 2) . '%'),
				TextColumn::make('service_fee_rate')
					->label('Service Fee Rate')
					->sortable()
					->toggleable(isToggledHiddenByDefault: true)
					->formatStateUsing(fn($state) => number_format($state, 2) . '%'),
				TextColumn::make('tenor')
					->label('Tenor')
					->sortable()
					->formatStateUsing(fn($state) => $state . ' Months')
					->toggleable(isToggledHiddenByDefault: false),
				TextColumn::make('start_date')
					->label('Start Date')
					->date('M d, Y')
					->sortable()
					->sinceTooltip()
					->toggleable(isToggledHiddenByDefault: false),
				TextColumn::make('status')
					->label('Status')
					->badge()
					->color(fn(string $state): string => match ($state) {
						'paid'            => 'success',
						'partial_payment' => 'info',
						'ongoing'         => 'warning',
						default           => 'primary'
					}),
				TextColumn::make('payment_progress')
					->label('Progress')
					->getStateUsing(fn($record) => "{$record->paid_installments_count}/{$record->total_installments_count}")
					->badge()
					->color('gray')
					->toggleable(isToggledHiddenByDefault: false),
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
			->filters([
				TrashedFilter::make()
					->searchable()
					->preload(true)
					->native(false),
			])
			->recordAction(null)
			->recordUrl(null)
			->recordActions([
				ActionGroup::make([
					ViewAction::make(),
					EditAction::make(),
				])
			])
			->toolbarActions([
				BulkActionGroup::make([
					DeleteBulkAction::make(),
					ForceDeleteBulkAction::make(),
					RestoreBulkAction::make(),
				]),
			]);
	}
}
