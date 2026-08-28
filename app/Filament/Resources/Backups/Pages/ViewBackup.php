<?php

namespace App\Filament\Resources\Backups\Pages;

use App\Filament\Resources\Backups\Actions\DownloadCloudBackupAction;
use App\Filament\Resources\Backups\BackupResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBackup extends ViewRecord
{
  protected static string $resource = BackupResource::class;

  protected function getHeaderActions(): array
  {
    return [
      DownloadCloudBackupAction::make(),
      DeleteAction::make(),
      ForceDeleteAction::make(),
      RestoreAction::make(),
    ];
  }
}

