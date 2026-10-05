<?php

namespace App\Console\Commands;

use App\Jobs\ProcessCalendarRemindersJob;
use Illuminate\Console\Command;

class ProcessCalendarReminders extends Command
{
  protected $signature   = 'calendar:process-reminders';
  protected $description = 'Process missed calendar reminders';

  public function handle(): int
  {
    ProcessCalendarRemindersJob::dispatchSync();

    $this->info('Calendar reminders processed.');

    return self::SUCCESS;
  }
}
