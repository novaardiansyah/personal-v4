<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Backup;
use App\Models\BackupSchedule;

class BackupScheduleObserver
{
  public function saving(BackupSchedule $backupSchedule): void
  {
    if (empty($backupSchedule->uid)) {
      $backupSchedule->uid = uuid7();
    }

    if (empty($backupSchedule->local_destination_path)) {
      $backupSchedule->local_destination_path = '/www/backup/sysadmin/others';
    }
  }

  public function creating(BackupSchedule $backupSchedule): void
  {
    if (empty($backupSchedule->uid)) {
      $backupSchedule->uid = uuid7();
    }
  }

  public function updating(BackupSchedule $backupSchedule): void
  {
    if (empty($backupSchedule->uid)) {
      $backupSchedule->uid = uuid7();
    }
  }

  public function created(BackupSchedule $backupSchedule): void
  {
    $this->_log('Created', $backupSchedule);
  }

  public function updated(BackupSchedule $backupSchedule): void
  {
    $this->_log('Updated', $backupSchedule);
  }

  public function deleted(BackupSchedule $backupSchedule): void
  {
    $backupSchedule->backups()->each(fn(Backup $backup) => $backup->delete());
    $this->_log('Deleted', $backupSchedule);
  }

  public function restored(BackupSchedule $backupSchedule): void
  {
    $this->_log('Restored', $backupSchedule);
  }

  public function forceDeleted(BackupSchedule $backupSchedule): void
  {
    $backupSchedule->backups()->withTrashed()->each(fn(Backup $backup) => $backup->forceDelete());
    $this->_log('Force Deleted', $backupSchedule);
  }

  private function _log(string $event, BackupSchedule $backupSchedule): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'Backup Schedule',
      'subject_type' => BackupSchedule::class,
      'subject_id'   => $backupSchedule->id,
    ], $backupSchedule);
  }
}
