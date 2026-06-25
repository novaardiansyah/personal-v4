<?php

namespace App\Filament\Resources\DebtInstallments;

use App\Filament\Resources\DebtInstallments\Pages\CreateDebtInstallment;
use App\Filament\Resources\DebtInstallments\Pages\EditDebtInstallment;
use App\Filament\Resources\DebtInstallments\Pages\ListDebtInstallments;
use App\Filament\Resources\DebtInstallments\Pages\ViewDebtInstallment;
use App\Filament\Resources\DebtInstallments\Schemas\DebtInstallmentForm;
use App\Filament\Resources\DebtInstallments\Schemas\DebtInstallmentInfolist;
use App\Filament\Resources\DebtInstallments\Tables\DebtInstallmentsTable;
use App\Models\DebtInstallment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DebtInstallmentResource extends Resource
{
    protected static ?string $model = DebtInstallment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'installment_number';

    public static function form(Schema $schema): Schema
    {
        return DebtInstallmentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DebtInstallmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DebtInstallmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDebtInstallments::route('/'),
            'create' => CreateDebtInstallment::route('/create'),
            'view' => ViewDebtInstallment::route('/{record}'),
            'edit' => EditDebtInstallment::route('/{record}/edit'),
        ];
    }
}
