<?php

namespace App\Filament\Resources\DiscordServers\Pages;

use App\Filament\Resources\DiscordServers\DiscordServerResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditDiscordServer extends EditRecord
{
  protected static string $resource = DiscordServerResource::class;

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
