<?php

/*
 * Project Name: personal-v4
 * File: UptimeMonitorStatus.php
 * Created Date: Wednesday February 11th 2026
 * 
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 * 
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum UptimeMonitorStatus: string implements HasLabel
{
  case UP   = 'up';
  case DOWN = 'down';
  case SLOW = 'slow';

  public function getLabel(): ?string
  {
    return match ($this) {
      self::UP   => 'UP',
      self::DOWN => 'DOWN',
      self::SLOW => 'SLOW',
    };
  }

  public function getColor(): string
  {
    return match ($this) {
      self::UP   => 'success',
      self::DOWN => 'danger',
      self::SLOW => 'warning',
    };
  }
}
