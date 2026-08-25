<?php

namespace App\Filament\Resources\BackupStorages\Pages;

use App\Filament\Resources\BackupStorages\BackupStorageResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditBackupStorage extends EditRecord
{
  protected static string $resource = BackupStorageResource::class;

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
