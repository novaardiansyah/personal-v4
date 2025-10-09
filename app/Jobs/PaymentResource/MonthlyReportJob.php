<?php

namespace App\Jobs\PaymentResource;

use App\Mail\PaymentResource\MonthlyReportMail;
use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Services\PaymentService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class MonthlyReportJob implements ShouldQueue
{
  use Queueable;

  /**
   * Create a new job instance.
   */
  public function __construct(public array $data)
  {
    //
  }

  /**
   * Execute the job.
   */
  public function handle(): void
  {
    \Log::info('6753 --> MonthlyReportJob: Started.');

    $periode = $this->data['periode'];
    $year    = Carbon::parse($periode)->format('Y');
    $month   = Carbon::parse($periode)->format('m');

    $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth()->format('Y-m-d');
    $endDate   = Carbon::createFromDate($year, $month, 1)->endOfMonth()->format('Y-m-d');
    $now       = Carbon::now()->toDateTimeString();

    $send = array_merge($this->data, [
      'filename'   => 'monthly-payment-report',
      'title'      => 'Laporan keuangan bulanan',
      'start_date' => $startDate,
      'end_date'   => $endDate,
      'now'        => $now,
    ]);

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

    $startDate = Carbon::parse($startDate);
    $endDate   = Carbon::parse($endDate);
    $periode   = '-';

    if ($startDate->isSameDay($endDate)) {
      $periode = $startDate->translatedFormat('d F Y');
    } else {
      $startFormat = $startDate->isSameMonth($endDate) ? 'd' : 'd F Y';
      $periode = $startDate->translatedFormat($startFormat) . ' - ' . $endDate->translatedFormat('d F Y');
    }

    $data = [
      'log_name'         => 'monthly_payment_notification',
      'email'            => $this->data['user']->email ?? '',
      'author_name'      => getSetting('author_name'),
      'subject'          => 'Notifikasi: Ringkasan Laporan Keuangan Bulanan',
      'payment_accounts' => PaymentAccount::orderBy('deposit', 'desc')->get()->toArray(),
      'payment'          => $payment->toArray(),
      'periode'          => $periode,
      'created_at'       => $now,
      'attachments' => [
        storage_path('app/' . $pdf['filepath']),
      ],
    ];
    Mail::to($data['email'])->queue(new MonthlyReportMail($data));

    \Log::info('6754 --> MonthlyReportJob: Finished.');
  }
}
