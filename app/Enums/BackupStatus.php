<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum BackupStatus: string implements HasLabel, HasColor, HasIcon
{
  case Pending = 'pending';
  case Success = 'success';
  case Failed  = 'failed';

  public function getLabel(): ?string
  {
    return match ($this) {
      self::Pending => 'Pending',
      self::Success => 'Success',
      self::Failed  => 'Failed',
    };
  }

  public function getColor(): string|array|null
  {
    return match ($this) {
      self::Pending => 'warning',
      self::Success => 'success',
      self::Failed  => 'danger',
    };
  }

  public function getIcon(): ?string
  {
    return match ($this) {
      self::Pending => 'heroicon-o-clock',
      self::Success => 'heroicon-o-check-circle',
      self::Failed  => 'heroicon-o-x-circle',
    };
  }
}
