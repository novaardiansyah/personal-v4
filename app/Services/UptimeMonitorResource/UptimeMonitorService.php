<?php

/*
 * Project Name: personal-v4
 * File: UptimeMonitorService.php
 * Created Date: Saturday February 8th 2026
 *
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 *
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

declare(strict_types=1);

namespace App\Services\UptimeMonitorResource;

use App\Models\UptimeMonitor;
use Illuminate\Support\Facades\Http;

class UptimeMonitorService
{
  public function check(UptimeMonitor $monitor): bool
  {
    try {
      $isHealthy = Http::timeout(30)->get($monitor->url)->successful();
    } catch (\Throwable $e) {
      $isHealthy = false;
    }

    $monitor->total_checks = ($monitor->total_checks ?? 0) + 1;
    $monitor->last_checked_at = now();

    $isHealthy
      ? $monitor->healthy_checks = ($monitor->healthy_checks ?? 0) + 1
      : $monitor->unhealthy_checks = ($monitor->unhealthy_checks ?? 0) + 1;

    $isHealthy
      ? $monitor->last_healthy_at = now()
      : $monitor->last_unhealthy_at = now();

    $monitor->save();

    return $isHealthy;
  }

  public function runScheduledChecks(): array
  {
    $results = [
      'total'     => 0,
      'healthy'   => 0,
      'unhealthy' => 0,
    ];

    UptimeMonitor::query()
      ->where('is_active', true)
      ->where(function ($query) {
        $query->whereNull('last_checked_at')
          ->orWhereRaw('TIMESTAMPDIFF(SECOND, last_checked_at, NOW()) >= `interval`');
      })
      ->chunkById(50, function ($monitors) use (&$results) {
        foreach ($monitors as $monitor) {
          $results['total']++;

          if ($this->check($monitor)) {
            $results['healthy']++;
          } else {
            $results['unhealthy']++;
          }
        }
      });

    return $results;
  }
}
