<?php

namespace App\Filament\Resources\BackupSchedules\Pages;

use App\Filament\Resources\BackupSchedules\BackupScheduleResource;
use App\Filament\Resources\BackupSchedules\Widgets\BackupOverviewWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBackupSchedules extends ListRecords
{
  protected static string $resource = BackupScheduleResource::class;

  protected function getHeaderActions(): array
  {
    return [
      CreateAction::make(),
    ];
  }

  protected function getHeaderWidgets(): array
  {
    return [
      BackupOverviewWidget::class,
    ];
  }
}
