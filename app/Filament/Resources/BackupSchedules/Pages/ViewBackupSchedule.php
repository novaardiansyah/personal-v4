<?php

namespace App\Filament\Resources\BackupSchedules\Pages;

use App\Filament\Resources\BackupSchedules\Actions\SyncCountBackupAction;
use App\Filament\Resources\BackupSchedules\BackupScheduleResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBackupSchedule extends ViewRecord
{
    protected static string $resource = BackupScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            SyncCountBackupAction::make(),
        ];
    }
}
