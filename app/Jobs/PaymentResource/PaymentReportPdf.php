<?php

namespace App\Jobs\PaymentResource;

use App\Mail\PaymentResource\CustomReportMail;
use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Services\PaymentService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PaymentReportPdf implements ShouldQueue
{
  use Queueable;

  /**
   * Create a new job instance.
   */
  public function __construct(public array $data = [])
  {
    //
  }

  /**
   * Execute the job.
   */
  public function handle(): void
  {
    Log::info('3246 --> PaymentReportPdf: Started.');

    $startDate = $this->data['start_date'] ?? Carbon::now()->startOfMonth()->format('Y-m-d');
    $endDate   = $this->data['end_date'] ?? Carbon::now()->endOfMonth()->format('Y-m-d');
    $now       = Carbon::now()->toDateTimeString();
    $causer    = $this->data['user'] ?? getUser();

    $send = array_merge([
      'filename'     => 'custom-payment-report',
      'title'        => 'Laporan keuangan',
      'start_date'   => $startDate,
      'end_date'     => $endDate,
      'now'          => $now,
      'user'         => $causer,
      'notification' => $this->data['notification'] ?? false,
    ], $this->data);

    $pdf = PaymentService::make_pdf($send);

    $payment = Payment::selectRaw('
      SUM(CASE WHEN type_id = 1 AND date BETWEEN ? AND ? THEN amount ELSE 0 END) AS daily_expense,
      SUM(CASE WHEN type_id = 2 AND date BETWEEN ? AND ? THEN amount ELSE 0 END) AS daily_income,
      SUM(CASE WHEN type_id != 1 AND type_id != 2 AND date BETWEEN ? AND ? THEN amount ELSE 0 END) AS daily_other,
      COUNT(CASE WHEN type_id = 1 AND date BETWEEN ? AND ? THEN id END) AS daily_expense_count,
      COUNT(CASE WHEN type_id = 2 AND date BETWEEN ? AND ? THEN id END) AS daily_income_count,
      COUNT(CASE WHEN type_id != 1 AND type_id != 2 AND date BETWEEN ? AND ? THEN id END) AS daily_other_count
    ', [
      $startDate, $endDate,
      $startDate, $endDate,
      $startDate, $endDate,
      $startDate, $endDate,
      $startDate, $endDate,
      $startDate, $endDate
    ])->first();

    Carbon::setLocale('id');

    $carbonStartDate = Carbon::parse($startDate);
    $carbonEndDate   = Carbon::parse($endDate);
    $periode         = '-';

    if ($carbonStartDate->isSameDay($carbonEndDate)) {
      $periode = $carbonStartDate->translatedFormat('d F Y');
    } else {
      $startFormat = $carbonStartDate->isSameMonth($carbonEndDate) ? 'd' : 'd F Y';
      $periode     = $carbonStartDate->translatedFormat($startFormat) . ' - ' . $carbonEndDate->translatedFormat('d F Y');
    }

    $send_to_email = $this->data['send_to_email'] ?? false;

    if ($send_to_email) {
      $data = [
        'log_name'         => 'custom_payment_notification',
        'email'            => getSetting('custom_payment_email'),
        'author_name'      => getSetting('author_name'),
        'subject'          => 'Notifikasi: Laporan Keuangan (' . $periode . ')',
        'payment_accounts' => PaymentAccount::orderBy('deposit', 'desc')->get()->toArray(),
        'payment'          => $payment->toArray(),
        'periode'          => $periode,
        'created_at'       => $now,
        'attachments' => [
          $pdf['fullpath'],
        ],
      ];
  
      Mail::to($data['email'])->queue(new CustomReportMail($data));
      $html = (new CustomReportMail($data))->render();
  
      saveActivityLog([
        'log_name'    => 'Notification',
        'description' => 'Custom Payment Report by ' . $causer->name,
        'event'       => 'Mail Notification',
        'properties' => [
          'email'       => $data['email'],
          'subject'     => $data['subject'],
          'attachments' => $data['attachments'],
          'html'        => $html,
        ],
      ]);
    }

    Log::info('3247 --> PaymentReportPdf: Finished.');
  }
}
