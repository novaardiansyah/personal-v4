<?php

namespace App\Filament\Resources\AiAssistantContexts\Pages;

use App\Filament\Resources\AiAssistantContexts\AiAssistantContextResource;
use App\Filament\Resources\AiAssistantContexts\Widgets\AiAssistantStatsWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAiAssistantContexts extends ListRecords
{
  protected static string $resource = AiAssistantContextResource::class;

  protected function getHeaderActions(): array
  {
    return [
      CreateAction::make(),
    ];
  }

  protected function getHeaderWidgets(): array
  {
    return [
      AiAssistantStatsWidget::class,
    ];
  }
}
