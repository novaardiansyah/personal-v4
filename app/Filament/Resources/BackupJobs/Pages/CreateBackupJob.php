<?php

namespace App\Filament\Resources\BackupJobs\Pages;

use App\Filament\Resources\BackupJobs\BackupJobResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBackupJob extends CreateRecord
{
  protected static string $resource = BackupJobResource::class;
}
