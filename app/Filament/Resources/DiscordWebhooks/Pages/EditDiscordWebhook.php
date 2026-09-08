<?php

namespace App\Filament\Resources\DiscordWebhooks\Pages;

use App\Filament\Resources\DiscordWebhooks\DiscordWebhookResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditDiscordWebhook extends EditRecord
{
  protected static string $resource = DiscordWebhookResource::class;

  protected function getHeaderActions(): array
  {
    return [
      ViewAction::make(),
      DeleteAction::make(),
      ForceDeleteAction::make(),
      RestoreAction::make(),
    ];
  }
}
