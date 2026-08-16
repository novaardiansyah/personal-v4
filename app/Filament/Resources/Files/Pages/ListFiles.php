<?php

namespace App\Filament\Resources\Files\Pages;

use App\Filament\Resources\Files\FileResource;
use App\Filament\Resources\Files\Schemas\FileAction;
use Filament\Resources\Pages\ListRecords;

class ListFiles extends ListRecords
{
  protected static string $resource = FileResource::class;

  protected function getHeaderActions(): array
  {
    return [
      FileAction::create(),
    ];
  }
}
