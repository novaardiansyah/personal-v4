<?php

namespace App\Observers;

use App\Models\PushNotification;

class PushNotificationObserver
{
  /**
   * Handle the PushNotification "created" event.
   */
  public function created(PushNotification $pushNotification): void
  {
    if (!$pushNotification->data) {
      $pushNotification->data = [
        'timestamps' => now()->toDateTimeString(),
      ];
      $pushNotification->save();
    }
    
    $this->_log('Created', $pushNotification);
  }

  /**
   * Handle the PushNotification "updated" event.
   */
  public function updated(PushNotification $pushNotification): void
  {
    $this->_log('Updated', $pushNotification);
  }

  /**
   * Handle the PushNotification "deleted" event.
   */
  public function deleted(PushNotification $pushNotification): void
  {
    $this->_log('Deleted', $pushNotification);
  }

  /**
   * Handle the PushNotification "restored" event.
   */
  public function restored(PushNotification $pushNotification): void
  {
    $this->_log('Restored', $pushNotification);
  }

  /**
   * Handle the PushNotification "force deleted" event.
   */
  public function forceDeleted(PushNotification $pushNotification): void
  {
    $this->_log('Force Deleted', $pushNotification);
  }

  private function _log(string $event, PushNotification $pushNotification): void
  {
    saveActivityLog([
      'event' => $event,
      'model' => 'PushNotification',
      'subject_type' => PushNotification::class,
      'subject_id' => $pushNotification->id,
    ], $pushNotification);
  }
}
