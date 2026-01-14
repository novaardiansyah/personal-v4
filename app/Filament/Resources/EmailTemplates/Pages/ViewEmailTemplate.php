<?php

namespace App\Filament\Resources\EmailTemplates\Pages;

use App\Filament\Resources\EmailTemplates\EmailTemplateResource;
use App\Filament\Resources\EmailTemplates\Schemas\EmailTemplateAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEmailTemplate extends ViewRecord
{
  protected static string $resource = EmailTemplateResource::class;

  protected function getHeaderActions(): array
  {
    return [
      EmailTemplateAction::protected(),
      EmailTemplateAction::unProtected(),
      EmailTemplateAction::preview(),
      EditAction::make(),
    ];
  }
}
