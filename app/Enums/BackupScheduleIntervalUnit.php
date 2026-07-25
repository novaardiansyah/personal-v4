<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum BackupScheduleIntervalUnit: string implements HasLabel, HasColor, HasIcon
{
  case Minutes = 'minutes';
  case Hours   = 'hours';
  case Days    = 'days';
  case Weeks   = 'weeks';
  case Months  = 'months';

  public function getLabel(): ?string
  {
    return match ($this) {
      self::Minutes => 'Minutes',
      self::Hours   => 'Hours',
      self::Days    => 'Days',
      self::Weeks   => 'Weeks',
      self::Months  => 'Months',
    };
  }

  public function getColor(): string|array|null
  {
    return match ($this) {
      self::Minutes => 'info',
      self::Hours   => 'primary',
      self::Days    => 'success',
      self::Weeks   => 'warning',
      self::Months  => 'gray',
    };
  }

  public function getIcon(): ?string
  {
    return match ($this) {
      self::Minutes => 'heroicon-o-clock',
      self::Hours   => 'heroicon-o-clock',
      self::Days    => 'heroicon-o-calendar',
      self::Weeks   => 'heroicon-o-calendar-days',
      self::Months  => 'heroicon-o-calendar-days',
    };
  }
}
