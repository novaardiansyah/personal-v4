<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Observers\UptimeLogObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy([UptimeLogObserver::class])]
class UptimeLog extends Model
{
  protected $table = 'uptime_logs';
  protected $fillable = ['url', 'status', 'http_status', 'response_time', 'error', 'checked_at'];
  protected $casts = [
    'checked_at' => 'datetime'
  ];
}
