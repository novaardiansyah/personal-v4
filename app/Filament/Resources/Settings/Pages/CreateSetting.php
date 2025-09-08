<?php

namespace App\Filament\Resources\Settings\Pages;

use App\Filament\Resources\Settings\SettingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSetting extends CreateRecord
{
  protected static string $resource = SettingResource::class;

  protected function mutateFormDataBeforeCreate(array $data): array
  {
    if ($data['has_options'] ?? false) {
      $data['value'] = $data['value_option'];
    }

    return $data;
  }
}
