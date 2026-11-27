<?php

namespace App\Filament\Resources\AiAssistantContexts\Pages;

use App\Filament\Resources\AiAssistantContexts\AiAssistantContextResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditAiAssistantContext extends EditRecord
{
  protected static string $resource = AiAssistantContextResource::class;

  protected function getHeaderActions(): array
  {
    return [
      DeleteAction::make(),
      ForceDeleteAction::make(),
      RestoreAction::make(),
    ];
  }
}
