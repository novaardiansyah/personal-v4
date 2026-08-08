<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Backup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BackupObserver
{
  public function creating(Backup $backup): void
  {
    if (empty($backup->uid)) {
      $backup->uid = (string) Str::uuid7();
    }

    if (empty($backup->started_at)) {
      $backup->started_at = now();
    }

    if (empty($backup->server_name)) {
      $backup->server_name = gethostname() ?: config('app.name', 'laravel');
    }
  }

  public function created(Backup $backup): void
  {
    $this->_log('Created', $backup);
    $this->_syncScheduleCount($backup);
  }

  public function updated(Backup $backup): void
  {
    $this->_log('Updated', $backup);
    $this->_syncScheduleCount($backup);
  }

  public function deleted(Backup $backup): void
  {
    $this->_log('Deleted', $backup);
    $this->_syncScheduleCount($backup);
  }

  public function restored(Backup $backup): void
  {
    $this->_log('Restored', $backup);
    $this->_syncScheduleCount($backup);
  }

  public function forceDeleted(Backup $backup): void
  {
    $this->_log('Force Deleted', $backup);
    $this->_syncScheduleCount($backup);
  }

  private function _syncScheduleCount(Backup $backup): void
  {
    $schedule = $backup->backupJob?->backupSchedule;

    if ($schedule) {
      $count   = $schedule->backups()->count();
      $sumSize = (int) $schedule->backups()->sum(DB::raw('CAST(file_size AS BIGINT)'));

      $schedule->update([
        'count_backup'  => $count,
        'sum_file_size' => $sumSize,
      ]);
    }
  }

  private function _log(string $event, Backup $backup): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'Backup',
      'subject_type' => Backup::class,
      'subject_id'   => $backup->id,
    ], $backup);
  }
}
