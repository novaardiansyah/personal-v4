<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\BackupStorageProvider;

class BackupStorageProviderObserver
{
  public function creating(BackupStorageProvider $backupStorageProvider): void
  {
    if (empty($backupStorageProvider->uid)) {
      $backupStorageProvider->uid = uuid7();
    }
  }

  public function updating(BackupStorageProvider $backupStorageProvider): void
  {
    if (empty($backupStorageProvider->uid)) {
      $backupStorageProvider->uid = uuid7();
    }
  }

  public function created(BackupStorageProvider $backupStorageProvider): void
  {
    $this->_log('Created', $backupStorageProvider);
  }

  public function updated(BackupStorageProvider $backupStorageProvider): void
  {
    $this->_log('Updated', $backupStorageProvider);
  }

  public function deleted(BackupStorageProvider $backupStorageProvider): void
  {
    $this->_log('Deleted', $backupStorageProvider);
  }

  public function restored(BackupStorageProvider $backupStorageProvider): void
  {
    $this->_log('Restored', $backupStorageProvider);
  }

  public function forceDeleted(BackupStorageProvider $backupStorageProvider): void
  {
    $this->_log('Force Deleted', $backupStorageProvider);
  }

  private function _log(string $event, BackupStorageProvider $backupStorageProvider): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'Backup Storage Provider',
      'subject_type' => BackupStorageProvider::class,
      'subject_id'   => $backupStorageProvider->id,
    ], $backupStorageProvider);
  }
}
