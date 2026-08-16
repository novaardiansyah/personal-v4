<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\BackupScheduleIntervalUnit;
use App\Models\BackupSchedule;
use Illuminate\Support\Str;

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
    $this->_log('Deleted', $backupSchedule);
  }

  public function restored(BackupSchedule $backupSchedule): void
  {
    $this->_log('Restored', $backupSchedule);
  }

  public function forceDeleted(BackupSchedule $backupSchedule): void
  {
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
