<?php

namespace App\Enums;

enum FileDownloadStatus: string
{
  case Active = 'active';
  case Inactive = 'inactive';

  public function label(): string
  {
    return match ($this) {
      self::Active => 'Active',
      self::Inactive => 'Inactive',
    };
  }

  public function color(): string
  {
    return match ($this) {
      self::Active => 'success',
      self::Inactive => 'danger',
    };
  }
}
