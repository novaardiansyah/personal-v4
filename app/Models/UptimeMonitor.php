<?php

namespace App\Models;

use App\Enums\UptimeMonitorStatus;
use App\Observers\UptimeMonitorObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(UptimeMonitorObserver::class)]
class UptimeMonitor extends Model
{
  use SoftDeletes;

  protected $table = 'uptime_monitors';

  protected $fillable = ['code', 'url', 'name', 'interval', 'is_active', 'last_checked_at', 'last_healthy_at', 'last_unhealthy_at', 'total_checks', 'healthy_checks', 'unhealthy_checks', 'next_check_at', 'status'];

  protected $casts = [
    'is_active'         => 'boolean',
    'last_checked_at'   => 'datetime',
    'last_healthy_at'   => 'datetime',
    'last_unhealthy_at' => 'datetime',
    'next_check_at'     => 'datetime',
    'status'            => UptimeMonitorStatus::class,
  ];

  public function logs(): HasMany
  {
    return $this->hasMany(UptimeMonitorLog::class, 'uptime_monitor_id');
  }
}
