<?php

namespace App\Filament\Resources\BackupStorages\Pages;

use App\Filament\Resources\BackupStorages\BackupStorageResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBackupStorage extends ViewRecord
{
  protected static string $resource = BackupStorageResource::class;

  protected function getHeaderActions(): array
  {
    return [
      EditAction::make(),
    ];
  }
}
