<?php

declare(strict_types=1);

namespace App\Jobs\BackupResource;

use App\Models\BackupSchedule;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PruneBackupsJob implements ShouldQueue
{
  use Queueable;

  public function handle(): void
  {
    $schedules = BackupSchedule::where('is_enabled', true)->get();

    foreach ($schedules as $schedule) {
      $maxCount = (int) ($schedule->max_count_backup ?: 5);
      if ($maxCount <= 0) {
        continue;
      }

      $activeBackups = $schedule->backups()
        ->orderBy('created_at', 'asc')
        ->get();

      $totalActive = $activeBackups->count();
      if ($totalActive <= $maxCount) {
        continue;
      }

      $excessCount     = $totalActive - $maxCount;
      $backupsToDelete = $activeBackups->take($excessCount);

      foreach ($backupsToDelete as $backup) {
        try {
          $backup->deleteFiles();
          $backup->delete();
        } catch (Throwable $e) {
          Log::error("Failed to prune backup ID {$backup->id}", [
            'error'       => $e->getMessage(),
            'schedule_id' => $schedule->id,
          ]);
        }
      }

      DB::transaction(function () use ($schedule) {
        $schedule->count_backup  = $schedule->backups()->count();
        $schedule->sum_file_size = (int) $schedule->backups()->sum('file_size');
        $schedule->save();
      });
    }
  }
}
