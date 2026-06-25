<?php

namespace App\Filament\Resources\DebtInstallments\Pages;

use App\Filament\Resources\DebtInstallments\DebtInstallmentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditDebtInstallment extends EditRecord
{
    protected static string $resource = DebtInstallmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
