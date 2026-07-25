<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum BackupType: string implements HasLabel, HasColor, HasIcon
{
  case Full        = 'full';
  case Database    = 'database';
  case Files       = 'files';
  case Incremental = 'incremental';

  public function getLabel(): ?string
  {
    return match ($this) {
      self::Full        => 'Full',
      self::Database    => 'Database',
      self::Files       => 'Files',
      self::Incremental => 'Incremental',
    };
  }

  public function getColor(): string|array|null
  {
    return match ($this) {
      self::Full        => 'primary',
      self::Database    => 'info',
      self::Files       => 'success',
      self::Incremental => 'warning',
    };
  }

  public function getIcon(): ?string
  {
    return match ($this) {
      self::Full        => 'heroicon-o-server-stack',
      self::Database    => 'heroicon-o-circle-stack',
      self::Files       => 'heroicon-o-document-duplicate',
      self::Incremental => 'heroicon-o-document-plus',
    };
  }
}
