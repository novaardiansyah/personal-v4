<?php

namespace App\Filament\Resources\Emails\Pages;

use App\Filament\Resources\Emails\EmailResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageEmails extends ManageRecords
{
  protected static string $resource = EmailResource::class;

  protected function getHeaderActions(): array
  {
    return [
      CreateAction::make(),
      Action::make('create_with_template')
        ->label('Template email')
        ->color('primary')
        ->url(fn(): string => EmailResource::getUrl('create-with-template')),
    ];
  }
}
