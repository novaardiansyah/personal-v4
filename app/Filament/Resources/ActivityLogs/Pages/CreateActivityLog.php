<?php

namespace App\Filament\Resources\ActivityLogs\Pages;

use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use Filament\Resources\Pages\CreateRecord;

class CreateActivityLog extends CreateRecord
{
    protected static string $resource = ActivityLogResource::class;
}
