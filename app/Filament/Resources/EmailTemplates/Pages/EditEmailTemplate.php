<?php

namespace App\Filament\Resources\EmailTemplates\Pages;

use App\Filament\Resources\EmailTemplates\EmailTemplateResource;
use App\Filament\Resources\EmailTemplates\Schemas\EmailTemplateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditEmailTemplate extends EditRecord
{
  protected static string $resource = EmailTemplateResource::class;

  protected function getHeaderActions(): array
  {
    return [
      EmailTemplateAction::protected(),
      EmailTemplateAction::unProtected(),
      EmailTemplateAction::preview(),
      ViewAction::make(),
      DeleteAction::make(),
      ForceDeleteAction::make(),
      RestoreAction::make(),
    ];
  }
}
