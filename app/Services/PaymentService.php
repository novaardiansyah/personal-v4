<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\User;
use Carbon\Carbon;

class PaymentService
{
  public static function make_pdf(array $data)
  {
    \Log::info('4236 --> PaymentService::make_pdf(): Started.');

    $notification = $data['notification'] ?? false;
    $auto_close_tbody = $data['auto_close_tbody'] ?? false;
    
    $startDate = $data['start_date'] ?? now()->startOfMonth();
    $endDate   = $data['end_date'] ?? now()->endOfMonth();
    $now       = $data['now'] ?? now()->toDateTimeString();

    $carbonStartDate = Carbon::parse($startDate);
    $carbonEndDate   = Carbon::parse($endDate);
    $periode         = '-';

    if ($carbonStartDate->isSameDay($carbonEndDate)) {
      $periode = $carbonStartDate->translatedFormat('d F Y');
    } else {
      $startFormat = $carbonStartDate->isSameMonth($carbonEndDate) ? 'd' : 'd F Y';
      $periode = $carbonStartDate->translatedFormat($startFormat) . ' - ' . $carbonEndDate->translatedFormat('d F Y');
    }

    // ! Setup pdf attachment
    $mpdf          = new \Mpdf\Mpdf();
    $rowIndex      = 1;
    $totalExpense  = 0;
    $totalIncome   = 0;
    $totalTransfer = 0;
    $user          = getUser();

    $mpdf->WriteHTML(view('payment-resource.make-pdf.header', [
      'title'   => $data['title'] ?? 'Laporan keuangan',
      'now'     => carbonTranslatedFormat($now, 'd/m/Y H:i'),
      'periode' => $periode,
      'user'    => $user,
    ])->render());
    
    Payment::whereBetween('date', [$startDate, $endDate])
      ->orderBy('date', 'desc')
      ->chunk(200, function ($list) use ($mpdf, &$rowIndex, &$totalExpense, &$totalIncome, &$totalTransfer) {
        foreach ($list as $record) {
          $record->income  = (int) $record->type_id === PaymentType::INCOME ? $record->amount : 0;
          $record->expense = (int) $record->type_id === PaymentType::EXPENSE ? $record->amount : 0;

          if ((int) $record->type_id === PaymentType::INCOME || (int) $record->type_id === PaymentType::EXPENSE) {
            $record->amount  = 0;
          }

          $view = view('payment-resource.make-pdf.body', [
            'record'    => $record,
            'loopIndex' => $rowIndex++,
          ])->render();

          $mpdf->WriteHTML($view);

          if ((int) $record->type_id === PaymentType::EXPENSE) {
            $totalExpense += $record->expense;
          } elseif ((int) $record->type_id === PaymentType::INCOME) {
            $totalIncome += $record->income;
          } else {
            $totalTransfer += $record->amount;
          }
        }
    });

    $mpdf->WriteHTML('
      </tbody>
      <tfoot>
        <tr>
          <td colspan="4" style="text-align: center; font-weight: bold;">Total Transaksi</td>
          <td style="font-weight: bold;">'. toIndonesianCurrency($totalTransfer) .'</td>
          <td style="font-weight: bold;">'. toIndonesianCurrency($totalIncome) .'</td>
          <td style="font-weight: bold;">'. toIndonesianCurrency($totalExpense) .'</td>
        </tr>
      </tfoot>
    ');

    $result = makePdf($mpdf, $data['filename'] ?? 'payment-report', $user, notification: $notification, auto_close_tbody: $auto_close_tbody);

    \Log::info('4236 --> PaymentService::make_pdf(): Finished.');

    return $result;
  }
}
