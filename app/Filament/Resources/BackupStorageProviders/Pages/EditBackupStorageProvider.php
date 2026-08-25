<?php

namespace App\Filament\Resources\BackupStorageProviders\Pages;

use App\Filament\Resources\BackupStorageProviders\BackupStorageProviderResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditBackupStorageProvider extends EditRecord
{
  protected static string $resource = BackupStorageProviderResource::class;

  protected function getHeaderActions(): array
  {
    return [
      ViewAction::make(),
      DeleteAction::make(),
      ForceDeleteAction::make(),
      RestoreAction::make(),
    ];
  }
}
