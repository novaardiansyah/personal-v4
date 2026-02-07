<?php

/*
 * Project Name: personal-v4
 * File: UptimeMonitorJob.php
 * Created Date: Saturday February 8th 2026
 *
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 *
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

declare(strict_types=1);

namespace App\Jobs\UptimeMonitorResource;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\UptimeMonitorResource\UptimeMonitorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

class UptimeMonitorJob implements ShouldQueue
{
  use Queueable;

  public function handle(): void
  {
    $now    = Carbon::now()->toDateTimeString();
    $causer = getUser();

    $defaultLog = [
      'log_name'    => 'Console',
      'event'       => 'Scheduled',
      'description' => 'UptimeMonitorJob() Executed by ' . $causer->name,
      'causer_type' => User::class,
      'causer_id'   => $causer->id,
      'properties'  => [
        'now' => $now,
      ],
    ];

    $startLog = saveActivityLog($defaultLog);

    $service = new UptimeMonitorService();
    $results = $service->runScheduledChecks();

    $defaultLog = array_merge($defaultLog, [
      'description'  => 'UptimeMonitorJob() Successfully Executed by ' . $causer->name,
      'subject_type' => ActivityLog::class,
      'subject_id'   => $startLog->id,
      'properties'   => array_merge($defaultLog['properties'], [
        'total_checked'   => $results['total'],
        'healthy_count'   => $results['healthy'],
        'unhealthy_count' => $results['unhealthy'],
      ]),
    ]);

    saveActivityLog($defaultLog);
  }
}
