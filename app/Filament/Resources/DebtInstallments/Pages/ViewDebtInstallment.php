<?php

namespace App\Filament\Resources\DebtInstallments\Pages;

use App\Filament\Resources\DebtInstallments\DebtInstallmentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDebtInstallment extends ViewRecord
{
	protected static string $resource = DebtInstallmentResource::class;

	protected function getHeaderActions(): array
	{
		return [
			EditAction::make(),
			DeleteAction::make(),
			ForceDeleteAction::make(),
			RestoreAction::make(),
		];
	}
}
