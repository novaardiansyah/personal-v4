<?php

namespace App\Observers;

use App\Models\Email;

class EmailObserver
{
  public function created(Email $email): void
  {
    $this->_log('Created', $email);
  }

  public function updated(Email $email): void
  {
    $this->_log('Updated', $email);
  }

  public function deleted(Email $email): void
  {
    $this->_log('Deleted', $email);
  }

  public function restored(Email $email): void
  {
    $this->_log('Restored', $email);
  }

  public function forceDeleted(Email $email): void
  {
    $this->_log('Force Deleted', $email);
  }

  private function _log(string $event, Email $email): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'Email',
      'subject_type' => Email::class,
      'subject_id'   => $email->id,
    ], $email);
  }
}
