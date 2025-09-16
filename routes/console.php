<?php

use App\Jobs\PaymentResource\ScheduledPaymentJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
  $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// ! Proses: Pembayaran Terjadwal
  Schedule::job(new ScheduledPaymentJob())
    ->dailyAt('00:05');