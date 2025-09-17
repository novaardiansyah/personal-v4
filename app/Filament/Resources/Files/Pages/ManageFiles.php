<?php

namespace App\Filament\Resources\Files\Pages;

use App\Filament\Resources\Files\FileResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFiles extends ManageRecords
{
  protected static string $resource = FileResource::class;

  protected function getHeaderActions(): array
  {
    return [
      // CreateAction::make(),
    ];
  }
}
