<?php

namespace App\Observers;

use App\Models\BlogTag;

class BlogTagObserver
{
  public function created(BlogTag $blogTag): void
  {
    $this->_log('Created', $blogTag);
  }

  public function updated(BlogTag $blogTag): void
  {
    $this->_log('Updated', $blogTag);
  }

  public function deleted(BlogTag $blogTag): void
  {
    $this->_log('Deleted', $blogTag);
  }

  public function restored(BlogTag $blogTag): void
  {
    $this->_log('Restored', $blogTag);
  }

  public function forceDeleted(BlogTag $blogTag): void
  {
    $this->_log('Force Deleted', $blogTag);
  }

  private function _log(string $event, BlogTag $blogTag): void
  {
    saveActivityLog([
      'event' => $event,
      'model' => 'Blog Tag',
      'subject_type' => BlogTag::class,
      'subject_id' => $blogTag->id,
    ], $blogTag);
  }
}
