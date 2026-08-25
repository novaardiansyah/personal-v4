<?php

namespace App\Filament\Resources\BackupStorages\Pages;

use App\Filament\Resources\BackupStorages\BackupStorageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBackupStorages extends ListRecords
{
  protected static string $resource = BackupStorageResource::class;

  protected function getHeaderActions(): array
  {
    return [
      CreateAction::make(),
    ];
  }
}
