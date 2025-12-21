<?php

namespace App\Observers;

use App\Models\BlogPost;

class BlogPostObserver
{
  public function created(BlogPost $blogPost): void
  {
    $this->_log('Created', $blogPost);
  }

  public function updated(BlogPost $blogPost): void
  {
    $this->_log('Updated', $blogPost);
  }

  public function deleted(BlogPost $blogPost): void
  {
    $this->_log('Deleted', $blogPost);
  }

  public function restored(BlogPost $blogPost): void
  {
    $this->_log('Restored', $blogPost);
  }

  public function forceDeleted(BlogPost $blogPost): void
  {
    $this->_log('Force Deleted', $blogPost);
  }

  private function _log(string $event, BlogPost $blogPost): void
  {
    saveActivityLog([
      'event' => $event,
      'model' => 'Blog Post',
      'subject_type' => BlogPost::class,
      'subject_id' => $blogPost->id,
    ], $blogPost);
  }
}
