<?php

namespace App\Filament\Resources\BackupStorageProviders\Pages;

use App\Filament\Resources\BackupStorageProviders\BackupStorageProviderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBackupStorageProviders extends ListRecords
{
  protected static string $resource = BackupStorageProviderResource::class;

  protected function getHeaderActions(): array
  {
    return [
      CreateAction::make(),
    ];
  }
}
