<?php

namespace App\Jobs;

use App\Jobs\SendCalendarReminderJob;
use App\Models\CalendarReminder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessCalendarRemindersJob implements ShouldQueue
{
  use Queueable;

  public function handle(): void
  {
    $reminders = CalendarReminder::where('remind_at', '<=', now())
      ->whereNull('reminded_at')
      ->get();

    foreach ($reminders as $reminder) {
      SendCalendarReminderJob::dispatch($reminder);
    }
  }
}
