<?php

namespace App\Filament\Resources\Generates\Actions;

class GenerateAction
{
  public static function getReviewID(string $prefix, string $separator, int $queue): string|null
  {
    if (!$prefix || !$separator || !$queue) return null;
    $res = $prefix . substr($separator, 0, 4) . str_pad($queue, 4, '0', STR_PAD_LEFT) . substr($separator, 4, 2);
    return $res;
  }
}
