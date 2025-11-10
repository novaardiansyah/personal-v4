<?php

namespace App\Jobs;

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
    $deletedCount = PersonalAccessToken::where('expires_at', '<', now())->delete();

    Log::info('CleanExpiredTokens: Removed expired tokens', [
      'deleted_count' => $deletedCount,
      'timestamp' => now()->toDateTimeString()
    ]);

    if ($deletedCount > 0) {
      Log::info("CleanExpiredTokens: Successfully deleted {$deletedCount} expired tokens");
    }
  }
}
