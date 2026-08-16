<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum FileType: int implements HasLabel, HasColor, HasIcon
{
  case LocalFile  = 1;
  case DeviceFile = 2;

  public function getLabel(): ?string
  {
    return match ($this) {
      self::LocalFile  => 'Local File',
      self::DeviceFile => 'Device File',
    };
  }

  public function getColor(): string|array|null
  {
    return match ($this) {
      self::LocalFile  => 'info',
      self::DeviceFile => 'success',
    };
  }

  public function getIcon(): ?string
  {
    return match ($this) {
      self::LocalFile  => 'heroicon-o-document',
      self::DeviceFile => 'heroicon-o-device-phone-mobile',
    };
  }
}
