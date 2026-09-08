<?php

namespace App\Filament\Resources\DiscordChannels\Pages;

use App\Filament\Resources\DiscordChannels\DiscordChannelResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditDiscordChannel extends EditRecord
{
  protected static string $resource = DiscordChannelResource::class;

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
