<?php

namespace App\Enums;

enum GallerySize: string
{
  case Original = 'original';
  case Small = 'small';
  case Medium = 'medium';
  case Large = 'large';

  public function label(): string
  {
    return match ($this) {
      self::Original => 'Original',
      self::Small    => 'Small',
      self::Medium   => 'Medium',
      self::Large    => 'Large',
    };
  }

  public function color(): string
  {
    return match ($this) {
      self::Original => 'gray',
      self::Small    => 'success',
      self::Medium   => 'warning',
      self::Large    => 'danger',
    };
  }
}

