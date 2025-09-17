<?php

use App\Jobs\FileResource\RemoveFileJob;
use App\Jobs\PaymentResource\ScheduledPaymentJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
  $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// ! Scheduled Payment
Schedule::job(new ScheduledPaymentJob())
  ->dailyAt('00:05');

// ! Scheduled File Deletion
Schedule::job(new RemoveFileJob())
  ->everyTwoHours();
