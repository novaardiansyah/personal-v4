<?php

namespace App\Jobs\PaymentResource;

use App\Exports\PaymentResource\PaymentExport;
use App\Mail\PaymentResource\CustomReportMail;
use App\Models\File;
use App\Models\Payment;
use App\Models\PaymentAccount;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class PaymentReportExcelJob implements ShouldQueue
{
  use Queueable;

  public function __construct(public array $data = []) {}

  public function handle(): void
  {
    Log::info('3248 --> PaymentReportExcel: Started.');

    $startDate          = $this->data['start_date'] ?? Carbon::now()->startOfMonth()->format('Y-m-d');
    $endDate            = $this->data['end_date'] ?? Carbon::now()->endOfMonth()->format('Y-m-d');
    $now                = Carbon::now()->toDateTimeString();
    $causer             = $this->data['user'] ?? getUser();
    $payment_account_id = $this->data['payment_account_id'] ?? null;

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

    $extension = 'xlsx';
    $directory = 'public/attachments';
    $filenameWithoutExtension = Str::orderedUuid()->toString();
    $filename = "{$filenameWithoutExtension}.{$extension}";
    $filepath = "{$directory}/{$filename}";
    $fullPath = storage_path("app/{$filepath}");

    if (!file_exists(dirname($fullPath))) {
      mkdir(dirname($fullPath), 0755, true);
    }

    Excel::store(
      new PaymentExport($startDate, $endDate, $payment_account_id),
      "attachments/{$filename}",
      'public'
    );

    $expiration = now()->addMonth();

    $fileUrl = URL::temporarySignedRoute(
      'download',
      $expiration,
      ['path' => $filenameWithoutExtension, 'extension' => $extension, 'directory' => $directory]
    );

    $notification = $this->data['notification'] ?? false;

    if ($notification) {
      Notification::make()
        ->title('Excel file ready')
        ->body('Your file is ready to download')
        ->icon('heroicon-o-arrow-down-tray')
        ->iconColor('success')
        ->actions([
          Action::make('download')
            ->label('Download')
            ->url($fileUrl)
            ->openUrlInNewTab()
            ->markAsRead()
            ->button()
        ])
        ->sendToDatabase($causer);
    }

    File::create([
      'user_id' => $causer->id,
      'file_name' => $filename,
      'file_path' => $filepath,
      'download_url' => $fileUrl,
      'scheduled_deletion_time' => $expiration,
    ]);

    $send_to_email = $this->data['send_to_email'] ?? false;

    if ($send_to_email) {
      $payment = Payment::selectRaw(
        'SUM(CASE WHEN type_id = 1 AND date BETWEEN ? AND ? THEN amount ELSE 0 END) AS daily_expense,
        SUM(CASE WHEN type_id = 2 AND date BETWEEN ? AND ? THEN amount ELSE 0 END) AS daily_income,
        SUM(CASE WHEN type_id != 1 AND type_id != 2 AND date BETWEEN ? AND ? THEN amount ELSE 0 END) AS daily_other,
        COUNT(CASE WHEN type_id = 1 AND date BETWEEN ? AND ? THEN id END) AS daily_expense_count,
        COUNT(CASE WHEN type_id = 2 AND date BETWEEN ? AND ? THEN id END) AS daily_income_count,
        COUNT(CASE WHEN type_id != 1 AND type_id != 2 AND date BETWEEN ? AND ? THEN id END) AS daily_other_count',
        [$startDate, $endDate, $startDate, $endDate, $startDate, $endDate, $startDate, $endDate, $startDate, $endDate, $startDate, $endDate]
      )->when($payment_account_id, function ($query) use ($payment_account_id) {
        $query->where('payment_account_id', $payment_account_id);
      })->first();

      $data = [
        'log_name'         => 'custom_payment_notification',
        'email'            => getSetting('custom_payment_email'),
        'author_name'      => getSetting('author_name'),
        'subject'          => 'Notifikasi: Laporan Keuangan Excel (' . $periode . ')',
        'payment_accounts' => PaymentAccount::orderBy('deposit', 'desc')->get()->toArray(),
        'payment'          => $payment->toArray(),
        'periode'          => $periode,
        'created_at'       => $now,
        'attachments'      => [$fullPath],
      ];

      Mail::to($data['email'])->queue(new CustomReportMail($data));
      $html = (new CustomReportMail($data))->render();

      saveActivityLog([
        'log_name'    => 'Notification',
        'description' => 'Custom Payment Excel Report by ' . $causer->name,
        'event'       => 'Mail Notification',
        'properties'  => [
          'email'       => $data['email'],
          'subject'     => $data['subject'],
          'attachments' => $data['attachments'],
          'html'        => $html,
        ],
      ]);
    }

    Log::info('3249 --> PaymentReportExcel: Finished.');
  }
}
