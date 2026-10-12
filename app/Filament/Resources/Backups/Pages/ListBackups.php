<?php

namespace App\Filament\Resources\Backups\Pages;

use App\Filament\Resources\Backups\Actions\PruneBackupAction;
use App\Filament\Resources\Backups\BackupResource;
use Filament\Resources\Pages\ListRecords;

class ListBackups extends ListRecords
{
  protected static string $resource = BackupResource::class;

  protected function getHeaderActions(): array
  {
    return [
      PruneBackupAction::make()
    ];
  }

  protected function getHeaderWidgets(): array
  {
    return [];
  }
}
