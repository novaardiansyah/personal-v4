<?php

namespace App\Observers;

use App\Models\ShortUrl;

class ShortUrlObserver
{
  /**
   * Handle the ShortUrl "created" event.
   */
  public function created(ShortUrl $shortUrl): void
  {
    $this->_log('Created', $shortUrl);
  }

  /**
   * Handle the ShortUrl "updated" event.
   */
  public function updated(ShortUrl $shortUrl): void
  {
    $this->_log('Updated', $shortUrl);
  }

  /**
   * Handle the ShortUrl "deleted" event.
   */
  public function deleted(ShortUrl $shortUrl): void
  {
    $this->_log('Deleted', $shortUrl);
  }

  /**
   * Handle the ShortUrl "restored" event.
   */
  public function restored(ShortUrl $shortUrl): void
  {
    $this->_log('Restored', $shortUrl);
  }

  /**
   * Handle the ShortUrl "force deleted" event.
   */
  public function forceDeleted(ShortUrl $shortUrl): void
  {
    $this->_log('Force Deleted', $shortUrl);
  }

  private function _log(string $event, ShortUrl $shortUrl): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'Short Url',
      'subject_type' => ShortUrl::class,
      'subject_id'   => $shortUrl->id,
    ], $shortUrl);
  }
}
