<?php

namespace App\Jobs\BackupResource;

use App\Models\BackupSchedule;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class SyncAllBackupSchedulesJob implements ShouldQueue
{
  use Queueable;

  public function __construct(
    public ?User $user = null
  ) {}

  public function handle(): void
  {
    $totalSchedules = 0;
    $totalBackups   = 0;
    $totalSize      = 0;

    BackupSchedule::chunk(10, function ($schedules) use (&$totalSchedules, &$totalBackups, &$totalSize): void {
      foreach ($schedules as $schedule) {
        $count   = $schedule->backups()->count();
        $sumSize = (int) $schedule->backups()->sum(DB::raw('CAST(file_size AS BIGINT)'));

        $schedule->update([
          'count_backup'  => $count,
          'sum_file_size' => $sumSize,
        ]);

        $totalSchedules++;
        $totalBackups += $count;
        $totalSize    += $sumSize;
      }
    });

    $user = $this->user ?? getUser();

    if ($user) {
      $formattedSize = sizeFormat((float) $totalSize);

      Notification::make()
        ->success()
        ->title('Backup Synchronization Completed')
        ->body("Synchronized {$totalSchedules} schedules with {$totalBackups} total backups ({$formattedSize}).")
        ->icon('heroicon-o-arrow-path')
        ->sendToDatabase($user);
    }
  }
}
