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
    'prev_properties' => 'array',
    'properties'      => 'array',
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
      'Login'         => 'danger',
    ];

    return $colors[$event] ?? 'primary';
  }

  public static function getLognameColor(string $event): string
  {
    $colors = [
      'Resource'     => 'primary',
      'Notification' => 'success',
    ];

    return $colors[$event] ?? 'primary';
  }

  /**
   * Get properties as readable string for display
   */
  public function getPropertiesStrAttribute(): ?array
  {
    if (!$this->properties) {
      return null;
    }

    $result = [];
    foreach ($this->properties as $key => $value) {
      if (is_array($value)) {
        $result[$key] = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
      } else {
        $result[$key] = $value;
      }
    }
    return $result;
  }

  /**
   * Get previous properties as readable string for display
   */
  public function getPrevPropertiesStrAttribute(): ?array
  {
    if (!$this->prev_properties) {
      return null;
    }

    $result = [];
    foreach ($this->prev_properties as $key => $value) {
      if (is_array($value)) {
        $result[$key] = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
      } else {
        $result[$key] = $value;
      }
    }
    return $result;
  }
}
