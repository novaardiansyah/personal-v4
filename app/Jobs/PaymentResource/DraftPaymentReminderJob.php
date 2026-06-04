<?php

namespace App\Jobs\PaymentResource;

use App\Mail\PaymentResource\DraftPaymentReminderMail;
use App\Models\ActivityLog;
use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\User;
use App\Services\PaymentResource\PaymentService;
use Illuminate\Support\Carbon;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class DraftPaymentReminderJob implements ShouldQueue
{
  use Queueable;

  public function __construct() {}

  public function handle(): void
  {
    $now    = now()->toDateTimeString();
    $today  = Carbon::now()->format('Y-m-d');
    $causer = getUser(userCode: getSetting('default_user_payment_report'));

    $defaultLog = [
      'log_name'    => 'Console',
      'event'       => 'Scheduled',
      'description' => 'DraftPaymentReminderJob() Executed by ' . $causer->name,
      'causer_type' => User::class,
      'causer_id'   => $causer->id,
      'properties'  => [
        'now'   => $now,
        'today' => $today,
      ],
    ];

    $startLog = saveActivityLog($defaultLog);

    $draftPayments = Payment::where('is_draft', true)
      ->where('date', $today)
      ->get();

    $draftExpense = $draftPayments->where('type_id', PaymentType::EXPENSE)->sum('amount');
    $draftIncome  = $draftPayments->where('type_id', PaymentType::INCOME)->sum('amount');
    $draftOther   = $draftPayments->whereNotIn('type_id', [PaymentType::EXPENSE, PaymentType::INCOME])->sum('amount');

    $date = carbonTranslatedFormat($now, 'd F Y', 'id');

    $data = [
      'log_name'      => 'draft_payment_reminder',
      'email'         => getSetting('daily_payment_email'),
      'author_name'   => getSetting('author_name'),
      'subject'       => 'Notifikasi: Pengingat Draft Transaksi (' . $date . ')',
      'date'          => $date,
      'draft_count'   => $draftPayments->count(),
      'draft_expense' => $draftExpense,
      'draft_income'  => $draftIncome,
      'draft_other'   => $draftOther,
      'created_at'    => $now,
      'attachments'   => [],
    ];

    if ($draftPayments->isNotEmpty()) {
      $pdfParams = [
        'title'      => 'Laporan Draft Transaksi',
        'start_date' => $today,
        'end_date'   => $today,
        'now'        => $now,
        'user'       => $causer,
        'is_draft'   => true,
      ];

      $pdf = PaymentService::make_pdf($pdfParams);

      $data['attachments'] = [
        $pdf['fullpath'],
      ];
    }

    Mail::to($data['email'])->queue(new DraftPaymentReminderMail($data));
    $html = (new DraftPaymentReminderMail($data))->render();

    saveActivityLog([
      'log_name'    => 'Notification',
      'description' => 'Draft Payment Reminder by ' . $causer->name,
      'event'       => 'Mail Notification',
      'properties'  => [
        'email'       => $data['email'],
        'subject'     => $data['subject'],
        'attachments' => $data['attachments'],
        'html'        => $html,
      ],
    ]);

    $defaultLog = array_merge($defaultLog, [
      'description'  => 'DraftPaymentReminderJob() Successfully Executed by ' . $causer->name,
      'subject_type' => ActivityLog::class,
      'subject_id'   => $startLog->id,
    ]);

    saveActivityLog($defaultLog);
  }
}
