<?php

namespace App\Enums;

enum BlogPostStatus: string
{
  case Draft = 'draft';
  case Published = 'published';
  case Scheduled = 'scheduled';
  case Archived = 'archived';

  public function label(): string
  {
    return match ($this) {
      self::Draft     => 'Draft',
      self::Published => 'Published',
      self::Scheduled => 'Scheduled',
      self::Archived  => 'Archived',
    };
  }

  public function color(): string
  {
    return match ($this) {
      self::Draft     => 'gray',
      self::Published => 'success',
      self::Scheduled => 'warning',
      self::Archived  => 'danger',
    };
  }

  public function icon(): string
  {
    return match ($this) {
      self::Draft     => 'heroicon-o-pencil',
      self::Published => 'heroicon-o-check-circle',
      self::Scheduled => 'heroicon-o-clock',
      self::Archived  => 'heroicon-o-archive-box',
    };
  }
}
