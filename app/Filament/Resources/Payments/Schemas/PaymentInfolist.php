<?php

namespace App\Filament\Resources\Payments\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PaymentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('type_id')
                    ->numeric(),
                TextEntry::make('user_id')
                    ->numeric(),
                TextEntry::make('payment_account_id')
                    ->numeric(),
                TextEntry::make('payment_account_to_id')
                    ->numeric(),
                TextEntry::make('code'),
                TextEntry::make('amount')
                    ->numeric(),
                IconEntry::make('has_items')
                    ->boolean(),
                TextEntry::make('date')
                    ->date(),
                IconEntry::make('is_scheduled')
                    ->boolean(),
                TextEntry::make('deleted_at')
                    ->dateTime(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
