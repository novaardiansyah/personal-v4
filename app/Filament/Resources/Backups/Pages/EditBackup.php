<?php

namespace App\Filament\Resources\Backups\Pages;

use App\Filament\Resources\Backups\Actions\DownloadCloudBackupAction;
use App\Filament\Resources\Backups\BackupResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditBackup extends EditRecord
{
  protected static string $resource = BackupResource::class;

  protected function getHeaderActions(): array
  {
    return [
      ViewAction::make(),
      DownloadCloudBackupAction::make(),
      DeleteAction::make(),
      ForceDeleteAction::make(),
      RestoreAction::make(),
    ];
  }
}

