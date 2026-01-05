<?php

namespace App\Filament\Resources\Emails\Pages;

use App\Filament\Resources\Emails\EmailResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageEmails extends ManageRecords
{
  protected static string $resource = EmailResource::class;

  protected function getHeaderActions(): array
  {
    return [
      CreateAction::make(),
      ActionEmail::createWithTemplate(),
    ];
  }
}
