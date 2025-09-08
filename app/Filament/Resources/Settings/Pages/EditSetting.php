<?php

namespace App\Filament\Resources\Settings\Pages;

use App\Filament\Resources\Settings\SettingResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSetting extends EditRecord
{
  protected static string $resource = SettingResource::class;

  protected function getHeaderActions(): array
  {
    return [
      ViewAction::make(),
      DeleteAction::make(),
      ForceDeleteAction::make(),
      RestoreAction::make(),
    ];
  }

  protected function mutateFormDataBeforeFill(array $data): array
  {
    $record = $this->getRecord();

    if ($data['has_options'] ?? $record->has_options) {
      $data['value_option'] = $data['value'];
    }

    return $data;
  }

  protected function mutateFormDataBeforeSave(array $data): array
  {
    $record = $this->getRecord();

    if ($data['has_options'] ?? $record->has_options) {
      $data['value'] = $data['value_option'];
    }

    return $data;
  }
}
