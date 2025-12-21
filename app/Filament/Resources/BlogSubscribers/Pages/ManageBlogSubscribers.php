<?php

namespace App\Filament\Resources\BlogSubscribers\Pages;

use App\Filament\Resources\BlogSubscribers\BlogSubscriberResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBlogSubscribers extends ManageRecords
{
    protected static string $resource = BlogSubscriberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
