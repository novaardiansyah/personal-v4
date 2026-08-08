<?php

namespace App\Jobs\BackupResource;

use App\Models\BackupSchedule;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CleanExcessBackupsJob implements ShouldQueue
{
  use Queueable;

  public function handle(): void
  {
    BackupSchedule::chunk(5, function ($schedules): void {
      foreach ($schedules as $schedule) {
        $maxCount = (int) ($schedule->max_count_backup ?? 5);

        if ($maxCount <= 0) {
          continue;
        }

        $totalBackups = $schedule->backups()->count();

        if ($totalBackups <= $maxCount) {
          continue;
        }

        $excessCount = $totalBackups - $maxCount;

        while ($excessCount > 0) {
          $chunkSize    = min($excessCount, 5);
          $backupsChunk = $schedule->backups()
            ->orderBy('created_at', 'asc')
            ->take($chunkSize)
            ->get();

          if ($backupsChunk->isEmpty()) {
            break;
          }

          foreach ($backupsChunk as $backup) {
            $this->deleteCloudFile($backup->cloud_file_path);
            $this->deleteLocalFile($backup->file_path);

            $backupJob = $backup->backupJob;

            $backup->delete();

            if ($backupJob && $backupJob->backups()->count() === 0) {
              $backupJob->delete();
            }
          }

          $excessCount -= $backupsChunk->count();
        }
      }
    });
  }

  private function deleteCloudFile(?string $cloudPath): void
  {
    if (empty($cloudPath)) {
      return;
    }

    try {
      if (Storage::disk('r2')->exists($cloudPath)) {
        Storage::disk('r2')->delete($cloudPath);
      }
    } catch (Throwable $e) {
      Log::error('Failed deleting cloud backup file on disk r2: ' . $e->getMessage(), [
        'cloud_path' => $cloudPath,
      ]);
    }
  }

  private function deleteLocalFile(?string $filePath): void
  {
    if (empty($filePath)) {
      return;
    }

    try {
      if (Storage::disk('public')->exists($filePath)) {
        Storage::disk('public')->delete($filePath);
      }
    } catch (Throwable $e) {
      Log::error('Failed deleting local backup file: ' . $e->getMessage(), [
        'file_path' => $filePath,
      ]);
    }
  }
}
