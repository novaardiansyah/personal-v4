<?php

/*
 * Project Name: personal-v4
 * File: UptimeMonitorObserver.php
 * Created Date: Saturday February 7th 2026
 *
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 *
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

declare(strict_types=1);

namespace App\Observers;

use App\Models\UptimeMonitor;

class UptimeMonitorObserver
{
  public function creating(UptimeMonitor $uptimeMonitor): void
  {
    $uptimeMonitor->code = getCode('uptime_monitor');
  }

  public function created(UptimeMonitor $uptimeMonitor): void
  {
    $this->_log('Created', $uptimeMonitor);
  }

  public function updated(UptimeMonitor $uptimeMonitor): void
  {
    $this->_log('Updated', $uptimeMonitor);
  }

  public function deleted(UptimeMonitor $uptimeMonitor): void
  {
    $this->_log('Deleted', $uptimeMonitor);
  }

  public function restored(UptimeMonitor $uptimeMonitor): void
  {
    $this->_log('Restored', $uptimeMonitor);
  }

  public function forceDeleted(UptimeMonitor $uptimeMonitor): void
  {
    $this->_log('Force Deleted', $uptimeMonitor);
  }

  private function _log(string $event, UptimeMonitor $uptimeMonitor): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'Uptime Monitor',
      'subject_type' => UptimeMonitor::class,
      'subject_id'   => $uptimeMonitor->id,
    ], $uptimeMonitor);
  }
}
