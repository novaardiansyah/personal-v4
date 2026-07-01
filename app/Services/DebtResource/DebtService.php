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
    $principal      = $debt->principal_amount;
    $tenor          = $debt->tenor;
    $interestRate   = $debt->interest_rate / 100;
    $serviceFeeRate = $debt->service_fee_rate / 100;

    $totalServiceFee       = round($principal * $serviceFeeRate);
    $monthlyServiceFeeBase = floor($totalServiceFee / $tenor);
    $totalVat              = round($totalServiceFee * 0.11);
    $monthlyVatBase        = floor($totalVat / $tenor);

    $totalPaymentEstimate    = self::calculateAnnuityPayment($principal, $interestRate, $tenor, $totalServiceFee + $totalVat);
    $totalMonthlyInstallment = round($totalPaymentEstimate);

    $remainingPrincipal = $principal;

    $paidTenor = 0;
    if ($debt->status === 'paid') {
      $paidTenor = $tenor;
    } elseif ($debt->status === 'partial_payment') {
      $paidTenor = (int) ($debt->paid_tenor ?? 0);
    }

    for ($i = 1; $i <= $tenor; $i++) {
      $isLast = ($i === $tenor);

      $serviceFee = $isLast ? ($totalServiceFee - ($monthlyServiceFeeBase * ($tenor - 1))) : $monthlyServiceFeeBase;
      $vat        = $isLast ? ($totalVat - ($monthlyVatBase * ($tenor - 1))) : $monthlyVatBase;

      if ($isLast) {
        $installmentPrincipal = $remainingPrincipal;
        $interest             = $totalMonthlyInstallment - ($installmentPrincipal + $serviceFee + $vat);
      } else {
        $interest              = round($remainingPrincipal * $interestRate);
        $installmentPrincipal  = $totalMonthlyInstallment - ($interest + $serviceFee + $vat);
        $remainingPrincipal   -= $installmentPrincipal;
      }

      $dueDate = Carbon::parse($debt->start_date)->addMonths($i - 1);
      $isPaid = ($i <= $paidTenor);
      $paymentId = null;

      if ($isPaid) {
        $payment = Payment::create([
          'type_id'            => PaymentType::EXPENSE,
          'user_id'            => $debt->user_id,
          'payment_account_id' => $debt->payment_account_id,
          'name'               => $debt->platform_name . ' - ' . $debt->name . '(' . $debt->code . ')',
          'amount'             => $totalMonthlyInstallment,
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
        'interest_amount'    => $interest,
        'service_fee'        => $serviceFee,
        'vat_amount'         => $vat,
        'total_amount'       => $totalMonthlyInstallment,
        'status'             => $isPaid ? 'paid' : 'unpaid',
        'paid_at'            => $isPaid ? $dueDate : null,
      ]);
    }
  }

  public static function calculateAnnuityPayment(float $p, float $r, int $n, float $extraFees): float
  {
    if ($r == 0) {
      return ($p + $extraFees) / $n;
    }
    $monthlyAnnuity = ($p * $r * pow(1 + $r, $n)) / (pow(1 + $r, $n) - 1);
    return $monthlyAnnuity + ($extraFees / $n);
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
