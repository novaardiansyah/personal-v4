<?php

namespace App\Filament\Resources\UptimeMonitors\Pages;

use App\Filament\Resources\UptimeMonitors\UptimeMonitorResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditUptimeMonitor extends EditRecord
{
  protected static string $resource = UptimeMonitorResource::class;

  protected function getHeaderActions(): array
  {
    return [
      ViewAction::make(),
      DeleteAction::make(),
      ForceDeleteAction::make(),
      RestoreAction::make(),
    ];
  }

  protected function getRedirectUrl(): string
  {
    $resource = static::getResource();
    return $resource::getUrl('index');
  }
}
