<?php

namespace App\Observers;

use App\Models\Generate;

class GenerateObserver
{
  /**
   * Handle the Generate "created" event.
   */
  public function created(Generate $generate): void
  {
    $this->_log('Created', $generate);
  }

  /**
   * Handle the Generate "updated" event.
   */
  public function updated(Generate $generate): void
  {
    $this->_log('Updated', $generate);
  }

  /**
   * Handle the Generate "deleted" event.
   */
  public function deleted(Generate $generate): void
  {
    $this->_log('Deleted', $generate);
  }

  /**
   * Handle the Generate "restored" event.
   */
  public function restored(Generate $generate): void
  {
    $this->_log('Restored', $generate);
  }

  /**
   * Handle the Generate "force deleted" event.
   */
  public function forceDeleted(Generate $generate): void
  {
    $this->_log('Force Deleted', $generate);
  }

  private function _log(string $event, Generate $generate): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'Generate',
      'subject_type' => Generate::class,
      'subject_id'   => $generate->id,
    ], $generate);
  }
}
