<?php

namespace App\Filament\Resources\BackupSchedules\Pages;

use App\Filament\Resources\BackupSchedules\Actions\BackupNowAction;
use App\Filament\Resources\BackupSchedules\Actions\SyncBackupAction;
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
      BackupNowAction::make(),
      SyncBackupAction::make(),
    ];
  }
}

