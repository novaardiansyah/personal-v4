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
    $totalDeletedFiles = 0;
    $totalDeletedSize  = 0;

    BackupSchedule::chunk(5, function ($schedules) use (&$totalDeletedFiles, &$totalDeletedSize): void {
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

            $totalDeletedFiles++;
            $totalDeletedSize += (int) ($backup->file_size ?? 0);

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

    $this->sendNotification($totalDeletedFiles, $totalDeletedSize);
  }

  private function sendNotification(int $totalDeletedFiles, int $totalDeletedSize): void
  {
    if ($totalDeletedFiles > 0) {
      $formattedSize = sizeFormat((float) $totalDeletedSize);

      $message = "Schedule Backup Cleanup\n\n"
        . "Total files deleted: {$totalDeletedFiles}\n"
        . "Total size deleted: {$formattedSize}";
    } else {
      $message = "Schedule Backup Cleanup\n\n"
        . "Backup cleanup check completed. No excess backup files were deleted.\n\n"
        . "Next check will run according to schedule.";
    }

    sendTelegramNotification($message);
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
