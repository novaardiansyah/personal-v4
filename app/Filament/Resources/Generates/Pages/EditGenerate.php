<?php

namespace App\Filament\Resources\Generates\Pages;

use App\Filament\Resources\Generates\GenerateResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditGenerate extends EditRecord
{
  protected static string $resource = GenerateResource::class;

  protected function getHeaderActions(): array
  {
    return [
      ViewAction::make(),
      DeleteAction::make(),
      ForceDeleteAction::make(),
      RestoreAction::make(),
    ];
  }

  protected function fillForm(): void
  {
    $record = $this->getRecord();
    $record->next_id = $record->getNextId();
    $this->fillFormWithDataAndCallHooks($record);
  }

  protected function getRedirectUrl(): string
  {
    $resource = static::getResource();
    return $resource::getUrl('index');
  }
}
