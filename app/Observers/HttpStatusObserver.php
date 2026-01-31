<?php

namespace App\Observers;

use App\Models\HttpStatus;

class HttpStatusObserver
{
  /**
   * Handle the HttpStatus "created" event.
   */
  public function created(HttpStatus $httpStatus): void
  {
    $this->_log('Created', $httpStatus);
  }

  /**
   * Handle the Item "updated" event.
   */
  public function updated(HttpStatus $httpStatus): void
  {
    $this->_log('Updated', $httpStatus);
  }

  /**
   * Handle the Item "deleted" event.
   */
  public function deleted(HttpStatus $httpStatus): void
  {
    $this->_log('Deleted', $httpStatus);
  }

  /**
   * Handle the Item "restored" event.
   */
  public function restored(HttpStatus $httpStatus): void
  {
    $this->_log('Restored', $httpStatus);
  }

  /**
   * Handle the Item "force deleted" event.
   */
  public function forceDeleted(HttpStatus $httpStatus): void
  {
    $this->_log('Force Deleted', $httpStatus);
  }

  private function _log(string $event, HttpStatus $httpStatus): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'Http Status',
      'subject_type' => HttpStatus::class,
      'subject_id'   => $httpStatus->id,
    ], $httpStatus);
  }
}
