<?php

namespace App\Jobs\PaymentResource;

use App\Mail\PaymentResource\ScheduledPaymentMail;
use App\Models\Payment;
use App\Models\PaymentAccount;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class ScheduledPaymentJob implements ShouldQueue
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
    \Log::info('3256 --> ScheduledPaymentJob: Started.');

    $result = Payment::scheduledPayment();

    if (!$result['status']) {
      \Log::info('3258 --> ScheduledPaymentJob: ' . $result['message']);
      return;
    }

    $now      = now()->toDateTimeString();
    $today    = Carbon::now()->format('Y-m-d');
    $tomorrow = Carbon::now()->addDay()->format('Y-m-d');

    // $send = [
    //   'filename'   => 'scheduled-payment-report',
    //   'title'      => 'Laporan keuangan terjadwal',
    //   'start_date' => $today,
    //   'end_date'   => $today,
    //   'now'        => $now,
    // ];

    // $pdf = PaymentService::make_pdf($send);
    
    $payment = Payment::selectRaw("
      SUM(CASE WHEN type_id = 1 THEN amount ELSE 0 END) AS daily_expense,
      SUM(CASE WHEN type_id = 2 THEN amount ELSE 0 END) AS daily_income,
      SUM(CASE WHEN type_id NOT IN (1, 2) THEN amount ELSE 0 END) AS daily_other,
      COUNT(CASE WHEN type_id = 1 THEN id END) AS daily_expense_count,
      COUNT(CASE WHEN type_id = 2 THEN id END) AS daily_income_count,
      COUNT(CASE WHEN type_id NOT IN (1, 2) THEN id END) AS daily_other_count
    ")->whereBetween('date', [$today, $tomorrow])->first();

    $data = [
      'author_name'      => getSetting('author_name'),
      'log_name'         => 'scheduled_payment_notification',
      'email'            => getSetting('scheduled_payment_email'),
      'subject'          => 'Notifikasi: Ringkasan Laporan Keuangan Terjadwal',
      'payment_accounts' => PaymentAccount::orderBy('deposit', 'desc')->get()->toArray(),
      'payment'          => $payment->toArray(),
      'date'             => carbonTranslatedFormat($now, 'd F Y'),
      'created_at'       => $now,
      // 'attachments' => [
      //   storage_path('app/' . $pdf['filepath']),
      // ],
    ];

    // $mailObj = new ScheduledPaymentMail($data);
    // $message = $mailObj->render();

    // EmailLog::create([
    //   'status_id'  => 2,
    //   'name'       => $data['log_name'],
    //   'email'      => $data['email'],
    //   'subject'    => $data['subject'],
    //   'message'    => $message,
    //   'created_at' => $now,
    //   'updated_at' => $now,
    // ]);
    
    Mail::to($data['email'])->queue(new ScheduledPaymentMail($data));

    \Log::info('3257 --> ScheduledPaymentJob: Finished.');
  }
}
