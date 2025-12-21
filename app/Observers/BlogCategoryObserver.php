<?php

namespace App\Observers;

use App\Models\BlogCategory;

class BlogCategoryObserver
{
  public function created(BlogCategory $blogCategory): void
  {
    $this->_log('Created', $blogCategory);
  }

  public function updated(BlogCategory $blogCategory): void
  {
    $this->_log('Updated', $blogCategory);
  }

  public function deleted(BlogCategory $blogCategory): void
  {
    $this->_log('Deleted', $blogCategory);
  }

  public function restored(BlogCategory $blogCategory): void
  {
    $this->_log('Restored', $blogCategory);
  }

  public function forceDeleted(BlogCategory $blogCategory): void
  {
    $this->_log('Force Deleted', $blogCategory);
  }

  private function _log(string $event, BlogCategory $blogCategory): void
  {
    saveActivityLog([
      'event' => $event,
      'model' => 'Blog Category',
      'subject_type' => BlogCategory::class,
      'subject_id' => $blogCategory->id,
    ], $blogCategory);
  }
}
