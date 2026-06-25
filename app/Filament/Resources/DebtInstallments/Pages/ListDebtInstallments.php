<?php

namespace App\Filament\Resources\DebtInstallments\Pages;

use App\Filament\Resources\DebtInstallments\DebtInstallmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDebtInstallments extends ListRecords
{
    protected static string $resource = DebtInstallmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
