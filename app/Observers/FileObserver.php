<?php

namespace App\Observers;

use App\Models\File;

class FileObserver
{
  /**
   * Handle the File "created" event.
   */
  public function created(File $file): void
  {
    $this->_log('Created', $file);
  }

  /**
   * Handle the File "updated" event.
   */
  public function updated(File $file): void
  {
    $this->_log('Updated', $file);
  }

  /**
   * Handle the File "deleted" event.
   */
  public function deleted(File $file): void
  {
    $this->_log('Deleted', $file);
  }

  /**
   * Handle the File "restored" event.
   */
  public function restored(File $file): void
  {
    $this->_log('Restored', $file);
  }

  /**
   * Handle the File "force deleted" event.
   */
  public function forceDeleted(File $file): void
  {
    $this->_log('Force Deleted', $file);
  }

  private function _log(string $event, File $file): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'File',
      'subject_type' => File::class,
      'subject_id'   => $file->id,
    ], $file);
  }
}
