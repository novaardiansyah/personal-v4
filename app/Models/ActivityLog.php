<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityLog extends Model
{
  use SoftDeletes;

  protected $guarded = ['id'];

  protected $table = 'activity_logs';

  protected $casts = [
    'prev_properties' => 'collection',
    'properties'      => 'collection',
  ];

  public function subject(): MorphTo
  {
    return $this->morphTo();
  }

  public function causer(): MorphTo
  {
    return $this->morphTo();
  }

  public static function getEventColor(string $event): string
  {
    $colors = [
      'Updated'       => 'info',
      'Created'       => 'success',
      'Deleted'       => 'danger',
      'Force Deleted' => 'danger',
      'Restored'      => 'warning',
    ];

    return $colors[$event] ?? 'primary';
  }
}
