<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
  /**
   * Handle the User "created" event.
   */
  public function created(User $user): void
  {
    $this->_log('Created', $user);
  }

  /**
   * Handle the User "updated" event.
   */
  public function updated(User $user): void
  {
    $this->_log('Updated', $user);
  }

  /**
   * Handle the User "deleted" event.
   */
  public function deleted(User $user): void
  {
    $this->_log('Deleted', $user);
  }

  /**
   * Handle the User "restored" event.
   */
  public function restored(User $user): void
  {
    $this->_log('Restored', $user);
  }

  /**
   * Handle the User "force deleted" event.
   */
  public function forceDeleted(User $user): void
  {
    $this->_log('Force Deleted', $user);
  }

  private function _log(string $event, User $user): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'User',
      'subject_type' => User::class,
      'subject_id'   => $user->id,
    ], $user);
  }
}
