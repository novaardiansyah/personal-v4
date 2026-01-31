<?php

namespace App\Observers;

use App\Models\UptimeLog;

class UptimeLogObserver
{
  /**
   * Handle the UptimeLog "created" event.
   */
  public function created(UptimeLog $uptimeLog): void
  {
    $this->_log('Created', $uptimeLog);
  }

  /**
   * Handle the Item "updated" event.
   */
  public function updated(UptimeLog $uptimeLog): void
  {
    $this->_log('Updated', $uptimeLog);
  }

  /**
   * Handle the Item "deleted" event.
   */
  public function deleted(UptimeLog $uptimeLog): void
  {
    $this->_log('Deleted', $uptimeLog);
  }

  /**
   * Handle the Item "restored" event.
   */
  public function restored(UptimeLog $uptimeLog): void
  {
    $this->_log('Restored', $uptimeLog);
  }

  /**
   * Handle the Item "force deleted" event.
   */
  public function forceDeleted(UptimeLog $uptimeLog): void
  {
    $this->_log('Force Deleted', $uptimeLog);
  }

  private function _log(string $event, UptimeLog $uptimeLog): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'Uptime Log',
      'subject_type' => UptimeLog::class,
      'subject_id'   => $uptimeLog->id,
    ], $uptimeLog);
  }
}
