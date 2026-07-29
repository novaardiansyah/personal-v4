<?php

namespace App\Filament\Resources\Backups\Pages;

use App\Filament\Resources\Backups\BackupResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBackup extends CreateRecord
{
  protected static string $resource = BackupResource::class;
}
