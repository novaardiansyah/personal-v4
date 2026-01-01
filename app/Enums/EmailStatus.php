<?php

namespace App\Enums;

enum EmailStatus: string
{
  case Draft = 'draft';
  case Sent = 'sent';

  public function label(): string
  {
    return match ($this) {
      self::Draft => 'Draft',
      self::Sent  => 'Sent',
    };
  }

  public function color(): string
  {
    return match ($this) {
      self::Draft => 'info',
      self::Sent  => 'success',
    };
  }
}
