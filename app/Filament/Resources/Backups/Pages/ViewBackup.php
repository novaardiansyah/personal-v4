<?php

namespace App\Filament\Resources\Backups\Pages;

use App\Filament\Resources\Backups\Actions\DownloadCloudBackupAction;
use App\Filament\Resources\Backups\BackupResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBackup extends ViewRecord
{
  protected static string $resource = BackupResource::class;

  protected function getHeaderActions(): array
  {
    return [
      DownloadCloudBackupAction::make(),
      EditAction::make(),
    ];
  }
}

