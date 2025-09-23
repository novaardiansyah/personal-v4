<?php

namespace App\Observers;

use App\Models\Gallery;

class GalleryObserver
{
  /**
   * Handle the Gallery "created" event.
   */
  public function created(Gallery $gallery): void
  {
    $this->_log('Created', $gallery);
  }

  /**
   * Handle the Gallery "updated" event.
   */
  public function updated(Gallery $gallery): void
  {
    $this->_log('Updated', $gallery);
  }

  /**
   * Handle the Gallery "deleted" event.
   */
  public function deleted(Gallery $gallery): void
  {
    $this->_log('Deleted', $gallery);
  }

  /**
   * Handle the Gallery "restored" event.
   */
  public function restored(Gallery $gallery): void
  {
    $this->_log('Restored', $gallery);
  }

  /**
   * Handle the Gallery "force deleted" event.
   */
  public function forceDeleted(Gallery $gallery): void
  {
    $this->_log('Force Deleted', $gallery);
  }

  private function _log(string $event, Gallery $gallery): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'Gallery',
      'subject_type' => Gallery::class,
      'subject_id'   => $gallery->id,
    ], $gallery);
  }
}