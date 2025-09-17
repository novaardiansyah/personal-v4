<?php

namespace App\Jobs\FileResource;

use App\Models\File;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;

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
    \Log::info('3556 --> RemoveFileJob: Started.');

    $now      = Carbon::now()->toDateTimeString();
    $twoHours = Carbon::now()->addHours(2)->toDateTimeString();

    File::where('has_been_deleted', false)
    ->whereBetween('scheduled_deletion_time', [$now, $twoHours])
    ->chunk(10, function (Collection $records) {
      foreach ($records as $record) {
        $record->removeFile();
      }
    });

    \Log::info('3557 --> RemoveFileJob: Finished.');
  }
}
