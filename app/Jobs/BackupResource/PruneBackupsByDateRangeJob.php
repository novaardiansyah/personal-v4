<?php

declare(strict_types=1);

namespace App\Jobs\BackupResource;

use App\Models\Backup;
use App\Models\BackupSchedule;
use App\Models\User;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class PruneBackupsByDateRangeJob implements ShouldQueue
{
  use Queueable;

  public function __construct(
    public string $startDate,
    public string $endDate,
    public ?int $userId = null,
  ) {}

  public function handle(): void
  {
    $start   = Carbon::parse($this->startDate)->startOfDay();
    $end     = Carbon::parse($this->endDate)->endOfDay();
    $backups = Backup::whereBetween('created_at', [$start, $end])->get();

    $totalBackups = $backups->count();
    $prunedCount  = 0;
    $scheduleIds  = [];

    foreach ($backups as $backup) {
      try {
        if ($backup->schedule_id) {
          $scheduleIds[] = $backup->schedule_id;
        }

        $backup->delete();
        $prunedCount++;
      } catch (Throwable $e) {
        Log::error("Failed to prune backup ID {$backup->id}", [
          'error'     => $e->getMessage(),
          'backup_id' => $backup->id,
        ]);
      }
    }

    $uniqueScheduleIds = array_unique($scheduleIds);
    foreach ($uniqueScheduleIds as $scheduleId) {
      $schedule = BackupSchedule::find($scheduleId);
      if ($schedule) {
        $schedule->count_backup  = $schedule->backups()->count();
        $schedule->sum_file_size = (int) $schedule->backups()->sum('file_size');
        $schedule->save();
      }
    }

    $user = $this->userId ? User::find($this->userId) : getUser();

    if ($user) {
      if ($totalBackups === 0) {
        Notification::make()
          ->title('Backup Pruning Completed')
          ->body("No backups found between {$this->startDate} and {$this->endDate}.")
          ->icon(Heroicon::OutlinedInformationCircle)
          ->iconColor('info')
          ->sendToDatabase($user);

        return;
      }

      $iconColor = $prunedCount === $totalBackups ? 'success' : 'warning';

      Notification::make()
        ->title('Backup Pruning Completed')
        ->body("Successfully pruned {$prunedCount} of {$totalBackups} backup(s) between {$this->startDate} and {$this->endDate}.")
        ->icon(Heroicon::OutlinedTrash)
        ->iconColor($iconColor)
        ->sendToDatabase($user);
    }
  }
}
