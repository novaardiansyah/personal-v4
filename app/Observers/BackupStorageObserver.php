<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\BackupStorage;

class BackupStorageObserver
{
  public function creating(BackupStorage $backupStorage): void
  {
    if (empty($backupStorage->uid)) {
      $backupStorage->uid = uuid7();
    }
  }

  public function updating(BackupStorage $backupStorage): void
  {
    if (empty($backupStorage->uid)) {
      $backupStorage->uid = uuid7();
    }
  }

  public function created(BackupStorage $backupStorage): void
  {
    $this->_log('Created', $backupStorage);
  }

  public function updated(BackupStorage $backupStorage): void
  {
    $this->_log('Updated', $backupStorage);
  }

  public function deleted(BackupStorage $backupStorage): void
  {
    $this->_log('Deleted', $backupStorage);
  }

  public function restored(BackupStorage $backupStorage): void
  {
    $this->_log('Restored', $backupStorage);
  }

  public function forceDeleted(BackupStorage $backupStorage): void
  {
    $this->_log('Force Deleted', $backupStorage);
  }

  private function _log(string $event, BackupStorage $backupStorage): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'Backup Storage',
      'subject_type' => BackupStorage::class,
      'subject_id'   => $backupStorage->id,
    ], $backupStorage);
  }
}
