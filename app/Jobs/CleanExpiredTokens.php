<?php

namespace App\Jobs;

use App\Models\ActivityLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

class CleanExpiredTokens implements ShouldQueue
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
    $now    = Carbon::now()->toDateTimeString();
    $causer = getUser();

    $defaultLog = [
      'log_name'    => 'Console',
      'event'       => 'Scheduled',
      'description' => 'CleanExpiredTokens() Executed by ' . $causer->name,
      'causer_type' => User::class,
      'causer_id'   => $causer->id,
      'properties'  => [
        'now'   => $now,
      ],
    ];

    $startLog     = saveActivityLog($defaultLog);
    $deletedCount = PersonalAccessToken::where('expires_at', '<', $now)->delete();

    $defaultLog = array_merge($defaultLog, [
      'description'  => 'CleanExpiredTokens() Successfully Executed by ' . $causer->name,
      'subject_type' => ActivityLog::class,
      'subject_id'   => $startLog->id,
      'properties'   => array_merge($defaultLog['properties'], [
        'deleted_count' => $deletedCount,
      ]),
    ]);

    saveActivityLog($defaultLog);
  }
}
