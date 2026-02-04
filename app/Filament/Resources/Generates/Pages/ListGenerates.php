<?php

namespace App\Filament\Resources\Generates\Pages;

use App\Filament\Resources\Generates\GenerateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGenerates extends ListRecords
{
    protected static string $resource = GenerateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
