<?php

declare(strict_types=1);

namespace App\Jobs\BackupResource;

use App\Models\Backup;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeleteScheduleBackupsJob implements ShouldQueue
{
  use Queueable;

  public function __construct(
    public int $scheduleId,
    public bool $forceDelete = false,
  ) {}

  public function handle(): void
  {
    $query = Backup::where('schedule_id', $this->scheduleId);

    if ($this->forceDelete) {
      $query->withTrashed()->each(fn(Backup $backup) => $backup->forceDelete());

      return;
    }

    $query->each(fn(Backup $backup) => $backup->delete());
  }
}
