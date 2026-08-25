<?php

namespace App\Filament\Resources\BackupStorageProviders\Pages;

use App\Filament\Resources\BackupStorageProviders\BackupStorageProviderResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBackupStorageProvider extends ViewRecord
{
  protected static string $resource = BackupStorageProviderResource::class;

  protected function getHeaderActions(): array
  {
    return [
      EditAction::make(),
    ];
  }
}
