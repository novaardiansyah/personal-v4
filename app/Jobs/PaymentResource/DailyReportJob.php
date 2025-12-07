<?php

namespace App\Jobs\PaymentResource;

use App\Mail\PaymentResource\DailyReportMail;
use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Services\PaymentService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class DailyReportJob implements ShouldQueue
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
    \Log::info('4256 --> DailyReportJob: Started.');

    $startDate = Carbon::now()->startOfWeek();
    $endDate   = Carbon::now()->endOfWeek();
    $now       = Carbon::now()->toDateTimeString();
    $today     = Carbon::now()->toDateString();
    $causer    = getUser();

    $send = [
      'filename'   => 'daily-payment-report',
      'title'      => 'Laporan keuangan harian',
      'start_date' => $startDate,
      'end_date'   => $endDate,
      'now'        => $now,
    ];

    $pdf = PaymentService::make_pdf($send);

    $payment = Payment::selectRaw('
      SUM(CASE WHEN type_id = 1 AND date = ? THEN amount ELSE 0 END) AS daily_expense,
      SUM(CASE WHEN type_id = 2 AND date = ? THEN amount ELSE 0 END) AS daily_income,
      SUM(CASE WHEN type_id != 1 AND type_id != 2 AND date = ? THEN amount ELSE 0 END) AS daily_other,
      COUNT(CASE WHEN type_id = 1 AND date = ? THEN id END) AS daily_expense_count,
      COUNT(CASE WHEN type_id = 2 AND date = ? THEN id END) AS daily_income_count,
      COUNT(CASE WHEN type_id != 1 AND type_id != 2 AND date = ? THEN id END) AS daily_other_count
    ', [
      $today, $today, $today, 
      $today, $today, $today
    ])->first();

    $data = [
      'log_name'         => 'daily_payment_notification',
      'email'            => getSetting('daily_payment_email'),
      'author_name'      => getSetting('author_name'),
      'subject'          => 'Notifikasi: Ringkasan Laporan Keuangan Harian',
      'payment_accounts' => PaymentAccount::orderBy('deposit', 'desc')->get()->toArray(),
      'payment'          => $payment->toArray(),
      'date'             => carbonTranslatedFormat($now, 'd F Y'),
      'created_at'       => $now,
      'attachments' => [
        storage_path('app/' . $pdf['filepath']),
      ],
    ];

    Mail::to($data['email'])->queue(new DailyReportMail($data));
    $html = (new DailyReportMail($data))->render();

    saveActivityLog([
      'log_name'    => 'Notification',
      'description' => 'Daily Payment Report by ' . $causer->name,
      'event'       => 'Mail Notification',
      'properties'  => [
        'email'       => $data['email'],
        'subject'     => $data['subject'],
        'attachments' => $data['attachments'],
        'html'        => $html,
      ],
    ]);

    \Log::info('4256 --> DailyReportJob: Finished.');
  }
}
