<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendTelegramNotificationJob implements ShouldQueue
{
  use Queueable;

  /**
   * Create a new job instance.
   */
  public function __construct(
    public string $message,
    public array $options = []
  ) {}

  /**
   * Execute the job.
   */
  public function handle(): void
  {
    sendTelegramNotification($this->message, $this->options);
  }
}
