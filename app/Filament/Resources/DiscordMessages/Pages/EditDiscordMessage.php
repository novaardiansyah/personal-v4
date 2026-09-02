<?php

namespace App\Filament\Resources\DiscordMessages\Pages;

use App\Filament\Resources\DiscordMessages\DiscordMessageResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditDiscordMessage extends EditRecord
{
  protected static string $resource = DiscordMessageResource::class;

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
