<?php

namespace App\Observers;

use App\Models\Gallery;
use Illuminate\Support\Facades\Storage;

class GalleryObserver
{
  public function creating(Gallery $gallery)
  {
    $file_name = pathinfo($gallery->file_path, PATHINFO_BASENAME);

    $gallery->user_id   = getUser()->id;
    $gallery->file_name = $file_name;
    $gallery->file_size = Storage::disk('public')->size($gallery->file_path);
  }

  public function created(Gallery $gallery): void
  {
    $this->_log('Created', $gallery);
  }

  public function updated(Gallery $gallery): void
  {
    $this->_log('Updated', $gallery);
  }

  public function deleted(Gallery $gallery): void
  {
    $this->_log('Deleted', $gallery);
  }

  public function restored(Gallery $gallery): void
  {
    $this->_log('Restored', $gallery);
  }

  public function forceDeleted(Gallery $gallery): void
  {
    if (Storage::disk('public')->exists($gallery->file_path)) {
      Storage::disk('public')->delete($gallery->file_path);
    }
  
    $this->_log('Force Deleted', $gallery);
  }

  private function _log(string $event, Gallery $gallery): void
  {
    saveActivityLog([
      'event' => $event,
      'model' => 'Gallery',
      'subject_type' => Gallery::class,
      'subject_id' => $gallery->id,
    ], $gallery);
  }
}
