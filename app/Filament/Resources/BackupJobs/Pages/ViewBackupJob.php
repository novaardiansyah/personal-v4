<?php

namespace App\Filament\Resources\BackupJobs\Pages;

use App\Filament\Resources\BackupJobs\BackupJobResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBackupJob extends ViewRecord
{
  protected static string $resource = BackupJobResource::class;

  protected function getHeaderActions(): array
  {
    return [
      EditAction::make(),
    ];
  }
}
