<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\BackupJob;

class BackupJobObserver
{
  public function created(BackupJob $backupJob): void
  {
    $this->_log('Created', $backupJob);
  }

  public function updated(BackupJob $backupJob): void
  {
    $this->_log('Updated', $backupJob);
  }

  public function deleted(BackupJob $backupJob): void
  {
    $this->_log('Deleted', $backupJob);
  }

  public function restored(BackupJob $backupJob): void
  {
    $this->_log('Restored', $backupJob);
  }

  public function forceDeleted(BackupJob $backupJob): void
  {
    $this->_log('Force Deleted', $backupJob);
  }

  private function _log(string $event, BackupJob $backupJob): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'Backup Job',
      'subject_type' => BackupJob::class,
      'subject_id'   => $backupJob->id,
    ], $backupJob);
  }
}
