<?php

namespace App\Filament\Resources\Generates\Pages;

use App\Filament\Resources\Generates\GenerateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGenerate extends CreateRecord
{
  protected static string $resource = GenerateResource::class;

  protected function getRedirectUrl(): string
  {
    $resource = static::getResource();
    return $resource::getUrl('index');
  }
}
