<?php

namespace App\Filament\Resources\BackupJobs\Pages;

use App\Filament\Resources\BackupJobs\BackupJobResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBackupJobs extends ListRecords
{
  protected static string $resource = BackupJobResource::class;

  protected function getHeaderActions(): array
  {
    return [
      CreateAction::make(),
    ];
  }
}
