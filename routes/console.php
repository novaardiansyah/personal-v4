<?php

use App\Jobs\BackupResource\PruneBackupsJob;
use App\Jobs\CleanExpiredTokens;
use App\Jobs\DiscordResource\SendPendingDiscordMessagesJob;
use App\Jobs\FileResource\RemoveFileJob;
use App\Jobs\PaymentResource\DailyReportJob;
use App\Jobs\PaymentResource\DraftPaymentReminderJob;
use App\Jobs\PaymentResource\MonthlyReportJob;
use App\Jobs\PaymentResource\ScheduledPaymentJob;
use App\Jobs\ProcessCalendarRemindersJob;
use App\Jobs\SubscriptionReminderJob;
use Illuminate\Support\Facades\Schedule;

// ! Scheduled Payment
Schedule::job(new ScheduledPaymentJob())
  ->dailyAt('23:59');

// ! Daily Payment Report
Schedule::job(new DailyReportJob([
  'notification'  => false,
  'send_to_email' => true,
  'user'          => getUser(userCode: getSetting('default_user_payment_report'))
]))
  ->dailyAt('23:59');

// ! Monthly Payment Report
Schedule::job(new MonthlyReportJob([
  'notification'  => false,
  'send_to_email' => true,
  'user'          => getUser(userCode: getSetting('default_user_payment_report'))
]))
  ->dailyAt('23:59');

// ! Scheduled File Deletion
Schedule::job(new RemoveFileJob())
  ->dailyAt('23:59');

// ! Clean Expired Tokens
Schedule::job(new CleanExpiredTokens())
  ->dailyAt('23:59');

// ! Draft Payment Reminder
Schedule::job(new DraftPaymentReminderJob())
  ->dailyAt('00:05');

// ! Process Calendar Reminders
Schedule::job(new ProcessCalendarRemindersJob())
  ->everyFiveMinutes();

// ! Subscription Reminder Job
Schedule::job(new SubscriptionReminderJob())
  ->dailyAt('05:00');

// ! Send Pending Discord Messages
Schedule::job(new SendPendingDiscordMessagesJob())
  ->everyMinute();

// ! Prune Backups Job
Schedule::job(new PruneBackupsJob())
  ->hourly();
