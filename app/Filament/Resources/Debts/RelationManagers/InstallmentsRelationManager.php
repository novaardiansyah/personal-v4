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

use App\Filament\Resources\DebtInstallments\Actions\PayAction;
use App\Filament\Resources\DebtInstallments\Tables\DebtInstallmentsTable;
use Filament\Actions\ActionGroup;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

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
					PayAction::make(),
				])
			]);
	}
}
