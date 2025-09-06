<?php

namespace App\Filament\Resources\Generates\Pages;

use App\Filament\Resources\Generates\GenerateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Str;

class ManageGenerates extends ManageRecords
{
  protected static string $resource = GenerateResource::class;

  protected function getHeaderActions(): array
  {
    return [
      CreateAction::make()
        ->mutateFormDataUsing(function (array $data): array {
          $data['prefix'] .= '-';
          $data['alias'] = Str::slug($data['alias'], '_');
          return $data;
        }),
    ];
  }
}
