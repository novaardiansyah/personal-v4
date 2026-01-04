<?php

namespace App\Observers;

use App\Models\FileDownload;
use Illuminate\Support\Str;

class FileDownloadObserver
{
  public function creating(FileDownload $fileDownload): void
  {
    $fileDownload->uid = Str::orderedUuid()->toString();
    $fileDownload->code = getCode('file_download');
  }

  public function created(FileDownload $fileDownload): void
  {
    $this->_log('Created', $fileDownload);
  }

  public function updated(FileDownload $fileDownload): void
  {
    $this->_log('Updated', $fileDownload);
  }

  public function deleted(FileDownload $fileDownload): void
  {
    $this->_log('Deleted', $fileDownload);
  }

  public function restored(FileDownload $fileDownload): void
  {
    $this->_log('Restored', $fileDownload);
  }

  public function forceDeleted(FileDownload $fileDownload): void
  {
    $this->_log('Force Deleted', $fileDownload);
  }

  private function _log(string $event, FileDownload $fileDownload): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'File Download',
      'subject_type' => FileDownload::class,
      'subject_id'   => $fileDownload->id,
    ], $fileDownload);
  }
}

