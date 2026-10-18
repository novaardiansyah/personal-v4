<?php

namespace App\Services\DebtResource;

use App\Models\Debt;
use App\Models\DebtInstallment;
use App\Models\Payment;
use App\Models\PaymentType;
use Illuminate\Support\Carbon;

class DebtService
{
  public static function generateInstallments(Debt $debt): void
  {
    $principal    = (int) $debt->principal_amount;
    $totalPayable = (int) $debt->total_payable_amount;
    $tenor        = max(1, (int) $debt->tenor);

    $monthlyPrincipal = intdiv($principal, $tenor);
    $monthlyTotal     = intdiv($totalPayable, $tenor);

    $paidTenor = 0;
    if ($debt->status === 'paid') {
      $paidTenor = $tenor;
    } elseif ($debt->status === 'partial_payment') {
      $paidTenor = (int) ($debt->paid_tenor ?? 0);
    }

    for ($i = 1; $i <= $tenor; $i++) {
      $isLast = ($i === $tenor);

      $installmentPrincipal = $isLast ? ($principal - ($monthlyPrincipal * ($tenor - 1))) : $monthlyPrincipal;
      $installmentTotal     = $isLast ? ($totalPayable - ($monthlyTotal * ($tenor - 1))) : $monthlyTotal;
      $installmentInterest  = $installmentTotal - $installmentPrincipal;

      $dueDate   = Carbon::parse($debt->start_date)->addMonths($i - 1);
      $isPaid    = ($i <= $paidTenor);
      $paymentId = null;

      if ($isPaid) {
        $payment = Payment::create([
          'type_id'            => PaymentType::EXPENSE,
          'user_id'            => $debt->user_id,
          'payment_account_id' => $debt->payment_account_id,
          'name'               => $debt->name . ' (' . $debt->code . ')',
          'amount'             => $installmentTotal,
          'date'               => $dueDate->format('Y-m-d'),
          'is_draft'           => true,
          'is_scheduled'       => false,
        ]);
        $paymentId = $payment->id;
      }

      DebtInstallment::create([
        'debt_id'            => $debt->id,
        'payment_id'         => $paymentId,
        'installment_number' => $i,
        'due_date'           => $dueDate,
        'principal_amount'   => $installmentPrincipal,
        'interest_amount'    => $installmentInterest,
        'penalty_amount'     => 0,
        'total_amount'       => $installmentTotal,
        'status'             => $isPaid ? 'paid' : 'unpaid',
        'paid_at'            => $isPaid ? $dueDate : null,
      ]);
    }
  }

  public static function calculateStatus(Debt $debt): string
  {
    $totalCount = $debt->installments()->count();
    $paidCount  = $debt->installments()->where('status', 'paid')->count();

    if ($paidCount === $totalCount) {
      return 'paid';
    } elseif ($paidCount > 0) {
      return 'partial_payment';
    }

    return 'ongoing';
  }
}
