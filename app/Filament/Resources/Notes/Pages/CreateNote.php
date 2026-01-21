<?php

namespace App\Filament\Resources\Notes\Pages;

use App\Filament\Resources\Notes\NoteResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNote extends CreateRecord
{
  protected static string $resource = NoteResource::class;

  protected function getRedirectUrl(): string
  {
    $resource = static::getResource();
    return $resource::getUrl('index');
  }
}
