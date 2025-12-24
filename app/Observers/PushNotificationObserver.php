<?php

namespace App\Observers;

use App\Models\PushNotification;

class PushNotificationObserver
{
  public function creating(PushNotification $pushNotification): void
  {
    $data = $pushNotification->data ?? [];
    $data['timestamp'] = now()->toDateTimeString();
    $pushNotification->data = $data;
  }

  public function created(PushNotification $pushNotification): void
  {
    $this->_log('Created', $pushNotification);
  }

  public function updated(PushNotification $pushNotification): void
  {
    $this->_log('Updated', $pushNotification);
  }

  public function deleted(PushNotification $pushNotification): void
  {
    $this->_log('Deleted', $pushNotification);
  }

  public function restored(PushNotification $pushNotification): void
  {
    $this->_log('Restored', $pushNotification);
  }

  public function forceDeleted(PushNotification $pushNotification): void
  {
    $this->_log('Force Deleted', $pushNotification);
  }

  private function _log(string $event, PushNotification $pushNotification): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'PushNotification',
      'subject_type' => PushNotification::class,
      'subject_id'   => $pushNotification->id,
    ], $pushNotification);
  }
}
