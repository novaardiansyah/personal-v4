<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\PaymentType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PaymentService
{
  public static function afterItemAttach(Payment $payment, Item $item, array $data): array
  {
    $item->update(['amount' => $data['price']]);

    $expense = $payment->amount + (int) $data['total'];
    $note = trim(($payment->name ?? '') . ', ' . "{$item->name} (x{$data['quantity']})", ', ');

    $payment->update(['amount' => $expense, 'name' => $note]);

    $paymentItem = PaymentItem::where('payment_id', $payment->id)
      ->where('item_id', $item->id)
      ->first();

    if ($paymentItem) {
      saveActivityLog([
        'event'        => 'Created',
        'model'        => 'Payment Item',
        'subject_type' => PaymentItem::class,
        'subject_id'   => $paymentItem->id,
      ], $paymentItem);
    }

    return [
      'success' => true,
      'amount' => $payment->amount,
      'formatted_amount' => toIndonesianCurrency($payment->amount),
      'note' => $note,
    ];
  }

  public static function beforeItemDetach(Payment $payment, Item $item, array $data): array
  {
    $expense = $payment->amount - (int) $data['total'];

    $itemName = $item->name . ' (x' . $data['quantity'] . ')';
    $note = trim(implode(', ', array_diff(explode(', ', $payment->name ?? ''), [$itemName])));

    $payment->update(['amount' => $expense, 'name' => $note]);

    $paymentItem = PaymentItem::where('payment_id', $payment->id)
      ->where('item_id', $item->id)
      ->first();

    if ($paymentItem) {
      saveActivityLog([
        'event'        => 'Deleted',
        'model'        => 'Payment Item',
        'subject_type' => PaymentItem::class,
        'subject_id'   => $paymentItem->id,
      ], $paymentItem);
    }

    return [
      'success' => true,
      'amount' => $payment->amount,
      'formatted_amount' => toIndonesianCurrency($payment->amount),
      'note' => $note,
    ];
  }

  public static function updateItemPivot(Payment $payment, Item $item, array $data): array
  {
    $oldQuantity = (int) $item->pivot->quantity;
    $oldTotal = (int) $item->pivot->total;
    $oldPrice = (int) $item->pivot->price;

    $newQuantity = (int) $data['quantity'];
    $newPrice = (int) $data['amount'];
    $newTotal = $newQuantity * $newPrice;

    $totalDiff = $newTotal - $oldTotal;
    $newPaymentAmount = $payment->amount + $totalDiff;

    $oldItemName = $item->name . ' (x' . $oldQuantity . ')';
    $newItemName = $item->name . ' (x' . $newQuantity . ')';
    $note = str_replace($oldItemName, $newItemName, $payment->name ?? '');

    $payment->update(['amount' => $newPaymentAmount, 'name' => $note]);
    $item->update(['amount' => $newPrice]);

    $paymentItem = PaymentItem::where('payment_id', $payment->id)
      ->where('item_id', $item->id)
      ->first();

    $properties = [
      'quantity' => $newQuantity,
      'price' => $newPrice,
      'total' => $newTotal,
    ];

    $payment->items()->updateExistingPivot($item->id, $properties);

    if ($paymentItem) {
      saveActivityLog([
        'event'        => 'Updated',
        'model'        => 'Payment Item',
        'subject_type' => PaymentItem::class,
        'subject_id'   => $paymentItem->id,
        'prev_properties' => [
          'quantity' => $paymentItem->quantity,
          'price'    => $paymentItem->price,
          'total'    => $paymentItem->total,
        ],
        'properties' => $properties,
      ], $paymentItem);
    }

    return [
      'success' => true,
      'amount' => $payment->amount,
      'formatted_amount' => toIndonesianCurrency($payment->amount),
      'note' => $note,
    ];
  }


  public static function manageDraft(Payment $record, bool $is_draft): array
  {
    if ($is_draft) {
      $record->is_draft = true;
      $record->save();

      return ['status' => true, 'message' => 'Draft status has been updated.'];
    }

    if (!$record->is_draft) {
      return ['status' => false, 'message' => 'This transaction is not a draft or has already been approved.'];
    }

    $type_id = intval($record->type_id);
    $amount = intval($record->amount);
    $payment_account = $record->payment_account;
    $payment_account_to = $record->payment_account_to;

    if ($type_id == PaymentType::INCOME) {
      $payment_account->deposit += $amount;
    } else {
      if ($payment_account->deposit < $amount) {
        return ['status' => false, 'message' => 'The amount in the payment account is not sufficient for the transaction.'];
      }

      if ($type_id == PaymentType::EXPENSE) {
        $payment_account->deposit -= $amount;
      } else if ($type_id == PaymentType::TRANSFER || $type_id == PaymentType::WITHDRAWAL) {
        if (!$payment_account_to) {
          return ['status' => false, 'message' => 'The destination payment account is invalid or not found.'];
        }

        $payment_account->deposit -= $amount;
        $payment_account_to->deposit += $amount;
      } else {
        return ['status' => false, 'message' => 'The selected transaction type is invalid.'];
      }
    }

    if ($payment_account->isDirty('deposit')) {
      $payment_account->save();
    }

    if ($payment_account_to && $payment_account_to->isDirty('deposit')) {
      $payment_account_to->save();
    }

    $record->is_draft = false;
    $record->save();

    return ['status' => true, 'message' => 'Draft has been approved and balance has been mutated.'];
  }

  public static function make_pdf(array $data): array
  {
    Log::info('4236 --> PaymentService::make_pdf(): Started.');

    Carbon::setLocale('id');

    $notification = $data['notification'] ?? false;
    $auto_close_tbody = $data['auto_close_tbody'] ?? false;

    $startDate = $data['start_date'] ?? now()->startOfMonth();
    $endDate = $data['end_date'] ?? now()->endOfMonth();
    $now = $data['now'] ?? now()->toDateTimeString();

    $carbonStartDate = Carbon::parse($startDate);
    $carbonEndDate = Carbon::parse($endDate);
    $periode = '-';

    if ($carbonStartDate->isSameDay($carbonEndDate)) {
      $periode = $carbonStartDate->translatedFormat('d F Y');
    } else {
      $startFormat = $carbonStartDate->isSameMonth($carbonEndDate) ? 'd' : 'd F Y';
      $periode = $carbonStartDate->translatedFormat($startFormat) . ' - ' . $carbonEndDate->translatedFormat('d F Y');
    }

    // ! Setup pdf attachment
    $mpdf = new \Mpdf\Mpdf();
    $rowIndex = 1;
    $totalExpense = 0;
    $totalIncome = 0;
    $totalTransfer = 0;
    $user = $data['user'] ?? getUser();

    $mpdf->WriteHTML(view('payment-resource.make-pdf.header', [
      'title' => $data['title'] ?? 'Laporan keuangan',
      'now' => carbonTranslatedFormat($now, 'd/m/Y H:i'),
      'periode' => $periode,
      'user' => $user,
    ])->render());

    Payment::whereBetween('date', [$startDate, $endDate])
      ->orderBy('date', 'desc')
      ->chunk(200, function ($list) use ($mpdf, &$rowIndex, &$totalExpense, &$totalIncome, &$totalTransfer) {
        foreach ($list as $record) {
          $record->income = (int) $record->type_id === PaymentType::INCOME ? $record->amount : 0;
          $record->expense = (int) $record->type_id === PaymentType::EXPENSE ? $record->amount : 0;

          if ((int) $record->type_id === PaymentType::INCOME || (int) $record->type_id === PaymentType::EXPENSE) {
            $record->amount = 0;
          }

          $view = view('payment-resource.make-pdf.body', [
            'record' => $record,
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
          <td style="font-weight: bold;">' . toIndonesianCurrency($totalTransfer) . '</td>
          <td style="font-weight: bold;">' . toIndonesianCurrency($totalIncome) . '</td>
          <td style="font-weight: bold;">' . toIndonesianCurrency($totalExpense) . '</td>
        </tr>
      </tfoot>
    ');

    $result = makePdf($mpdf, $user, notification: $notification, auto_close_tbody: $auto_close_tbody);

    Log::info('4236 --> PaymentService::make_pdf(): Finished.');

    return $result;
  }
}
