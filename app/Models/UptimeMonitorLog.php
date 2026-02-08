<?php

/*
 * Project Name: personal-v4
 * File: UptimeMonitorLog.php
 * Created Date: Sunday February 8th 2026
 *
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 *
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UptimeMonitorLog extends Model
{
  use SoftDeletes;

  protected $table = 'uptime_monitor_logs';

  protected $fillable = ['uptime_monitor_id', 'status_code', 'response_time_ms', 'is_healthy', 'error_message', 'checked_at'];

  protected $casts = [
    'is_healthy' => 'boolean',
    'checked_at' => 'datetime',
  ];

  public function uptimeMonitor(): BelongsTo
  {
    return $this->belongsTo(UptimeMonitor::class, 'uptime_monitor_id');
  }
}
