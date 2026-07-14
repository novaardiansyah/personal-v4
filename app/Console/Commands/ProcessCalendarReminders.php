<?php

namespace App\Console\Commands;

use App\Jobs\SendCalendarReminderJob;
use App\Models\CalendarReminder;
use Illuminate\Console\Command;

class ProcessCalendarReminders extends Command
{
  protected $signature   = 'calendar:process-reminders';
  protected $description = 'Process missed calendar reminders';

  public function handle(): int
  {
    $reminders = CalendarReminder::where('remind_at', '<=', now())
      ->whereNull('reminded_at')
      ->get();

    if ($reminders->isEmpty()) {
      $this->info('No pending reminders to process.');
      return self::SUCCESS;
    }

    foreach ($reminders as $reminder) {
      SendCalendarReminderJob::dispatch($reminder);
      $this->info("Dispatched reminder ID: {$reminder->id}");
    }

    $this->info("Processed {$reminders->count()} reminder(s).");
    return self::SUCCESS;
  }
}
