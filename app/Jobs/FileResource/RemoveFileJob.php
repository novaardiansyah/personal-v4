<?php

namespace App\Jobs\FileResource;

use App\Models\File;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RemoveFileJob implements ShouldQueue
{
  use Queueable;

  /**
   * Create a new job instance.
   */
  public function __construct()
  {
    //
  }

  /**
   * Execute the job.
   */
  public function handle(): void
  {
    Log::info('3556 --> RemoveFileJob: Started.');

    $now = Carbon::now()->toDateTimeString();

    $query = File::where('has_been_deleted', false)
      ->where('scheduled_deletion_time', '<=', $now);

    $count = $query->count();
    Log::info("3556 --> RemoveFileJob: Found {$count} files to delete.");

    $query->chunk(10, function (Collection $records) {
      foreach ($records as $record) {
        Log::info("3556 --> RemoveFileJob: Deleting file ID {$record->id}");
        $record->removeFile();
      }
    });

    Log::info('3557 --> RemoveFileJob: Finished.');
  }
}
