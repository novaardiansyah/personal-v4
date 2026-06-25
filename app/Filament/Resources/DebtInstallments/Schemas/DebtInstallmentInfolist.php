<?php

namespace App\Filament\Resources\DebtInstallments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class DebtInstallmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('debt_id')
                    ->numeric(),
                TextEntry::make('payment_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('installment_number')
                    ->numeric(),
                TextEntry::make('due_date')
                    ->date(),
                TextEntry::make('principal_amount')
                    ->numeric(),
                TextEntry::make('interest_amount')
                    ->numeric(),
                TextEntry::make('service_fee')
                    ->numeric(),
                TextEntry::make('vat_amount')
                    ->numeric(),
                TextEntry::make('penalty_amount')
                    ->numeric(),
                TextEntry::make('total_amount')
                    ->numeric(),
                TextEntry::make('status'),
                TextEntry::make('paid_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
