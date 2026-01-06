<?php

use App\Http\Controllers\Api\AuthController;
use App\Jobs\CleanExpiredTokens;
use App\Jobs\FileResource\RemoveFileJob;
use App\Jobs\PaymentResource\DailyReportJob;
use App\Jobs\PaymentResource\ScheduledPaymentJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
  $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// ! Scheduled Payment
Schedule::job(new ScheduledPaymentJob())
  ->dailyAt('23:59');

// ! Daily Payment Report
Schedule::job(new DailyReportJob(['notification' => false, 'send_to_email' => true]))
  ->dailyAt('23:59');

// ! Scheduled File Deletion
Schedule::job(new RemoveFileJob())
  ->dailyAt('23:59');

// ! Clean Expired Tokens
Schedule::job(new CleanExpiredTokens())
  ->dailyAt('23:59');
