<?php

/*
 * Project Name: personal-v4
 * File: DebtObserver.php
 * Created Date: Thursday June 25th 2026
 * 
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 * 
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

namespace App\Observers;

use App\Models\Debt;
use App\Models\DebtInstallment;
use App\Models\Payment;
use Illuminate\Support\Carbon;

class DebtObserver
{
  public function creating(Debt $debt): void
  {
    $debt->code    = getCode('debt');
    $debt->user_id = auth()->id() ?? getUser()?->id;
  }

  public function created(Debt $debt): void
  {
    $principal      = $debt->principal_amount;
    $tenor          = $debt->tenor;
    $interestRate   = $debt->interest_rate / 100;
    $serviceFeeRate = $debt->service_fee_rate / 100;

    $totalServiceFee       = round($principal * $serviceFeeRate);
    $monthlyServiceFeeBase = floor($totalServiceFee / $tenor);
    $totalVat              = round($totalServiceFee * 0.11);
    $monthlyVatBase        = floor($totalVat / $tenor);

    $totalPaymentEstimate    = $this->calculateAnnuityPayment($principal, $interestRate, $tenor, $totalServiceFee + $totalVat);
    $totalMonthlyInstallment = round($totalPaymentEstimate);

    $remainingPrincipal = $principal;

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

      DebtInstallment::create([
        'debt_id'            => $debt->id,
        'installment_number' => $i,
        'due_date'           => Carbon::parse($debt->start_date)->addMonths($i),
        'principal_amount'   => $installmentPrincipal,
        'interest_amount'    => $interest,
        'service_fee'        => $serviceFee,
        'vat_amount'         => $vat,
        'total_amount'       => $totalMonthlyInstallment,
        'status'             => 'unpaid',
      ]);
    }

    $this->recordDisbursementPayment($debt);
    $this->_log('Created', $debt);
  }

  public function updated(Debt $debt): void
  {
    $this->_log('Updated', $debt);
  }

  public function deleted(Debt $debt): void
  {
    $this->_log('Deleted', $debt);
  }

  private function calculateAnnuityPayment(float $p, float $r, int $n, float $extraFees): float
  {
    if ($r == 0) {
      return ($p + $extraFees) / $n;
    }
    $monthlyAnnuity = ($p * $r * pow(1 + $r, $n)) / (pow(1 + $r, $n) - 1);
    return $monthlyAnnuity + ($extraFees / $n);
  }

  private function recordDisbursementPayment(Debt $debt): void
  {
    Payment::create([
      'type_id'            => 2,
      'user_id'            => $debt->user_id,
      'payment_account_id' => $debt->payment_account_id,
      'code'               => getCode('payment'),
      'name'               => 'Pencairan Pinjaman: ' . $debt->platform_name . ' - ' . $debt->name,
      'amount'             => $debt->disbursement_amount,
      'date'               => $debt->start_date,
      'is_draft'           => false,
      'is_scheduled'       => false,
    ]);
  }

  private function _log(string $event, Debt $debt): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'Debt',
      'subject_type' => Debt::class,
      'subject_id'   => $debt->id,
    ], $debt);
  }
}
