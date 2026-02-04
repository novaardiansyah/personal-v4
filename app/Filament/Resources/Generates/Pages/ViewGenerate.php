<?php

namespace App\Filament\Resources\Generates\Pages;

use App\Filament\Resources\Generates\GenerateResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewGenerate extends ViewRecord
{
  protected static string $resource = GenerateResource::class;

  protected function getHeaderActions(): array
  {
    return [
      EditAction::make(),
    ];
  }
}
