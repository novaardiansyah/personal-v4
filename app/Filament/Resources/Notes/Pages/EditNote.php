<?php

namespace App\Filament\Resources\Notes\Pages;

use App\Filament\Resources\Notes\NoteResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditNote extends EditRecord
{
  protected static string $resource = NoteResource::class;

  protected function getHeaderActions(): array
  {
    return [
      ViewAction::make(),
    ];
  }

  protected function getRedirectUrl(): string
  {
    $resource = static::getResource();
    return $resource::getUrl('index');
  }
}
