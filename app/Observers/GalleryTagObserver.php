<?php

namespace App\Observers;

use App\Models\GalleryTag;

class GalleryTagObserver
{
  /**
   * Handle the GalleryTag "created" event.
   */
  public function created(GalleryTag $galleryTag): void
  {
    $this->_log('Created', $galleryTag);
  }

  /**
   * Handle the GalleryTag "updated" event.
   */
  public function updated(GalleryTag $galleryTag): void
  {
    $this->_log('Updated', $galleryTag);
  }

  /**
   * Handle the GalleryTag "deleted" event.
   */
  public function deleted(GalleryTag $galleryTag): void
  {
    $this->_log('Deleted', $galleryTag);
  }

  /**
   * Handle the GalleryTag "restored" event.
   */
  public function restored(GalleryTag $galleryTag): void
  {
    $this->_log('Restored', $galleryTag);
  }

  /**
   * Handle the GalleryTag "force deleted" event.
   */
  public function forceDeleted(GalleryTag $galleryTag): void
  {
    $this->_log('Force Deleted', $galleryTag);
  }

  private function _log(string $event, GalleryTag $galleryTag): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'Gallery Tag',
      'subject_type' => GalleryTag::class,
      'subject_id'   => $galleryTag->id,
    ], $galleryTag);
  }
}