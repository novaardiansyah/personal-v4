<?php

use App\Jobs\CleanExpiredTokens;
use App\Jobs\FileResource\RemoveFileJob;
use App\Jobs\PaymentResource\DailyReportJob;
use App\Jobs\PaymentResource\ScheduledPaymentJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
  $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// ! Scheduled Payment
Schedule::job(new ScheduledPaymentJob())
  ->dailyAt('00:05');

// ! Daily Payment Report
Schedule::job(new DailyReportJob())
  ->dailyAt('23:59');

// ! Scheduled File Deletion
Schedule::job(new RemoveFileJob())
  ->everyTwoHours();

// ! Clean Expired Tokens
Schedule::job(new CleanExpiredTokens())
  ->dailyAt('23:59');
