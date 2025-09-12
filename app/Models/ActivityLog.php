<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityLog extends Model
{
  use SoftDeletes;

  protected $guard = ['id'];

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
}
