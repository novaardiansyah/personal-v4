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
use App\Models\Payment;
use App\Services\DebtResource\DebtService;

class DebtObserver
{
  public function creating(Debt $debt): void
  {
    $debt->code    = getCode('debt');
    $debt->user_id = auth()->id() ?? getUser()?->id;
  }

  public function created(Debt $debt): void
  {
    DebtService::generateInstallments($debt);
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

  private function recordDisbursementPayment(Debt $debt): void
  {
    Payment::create([
      'type_id'            => 2,
      'user_id'            => $debt->user_id,
      'payment_account_id' => $debt->payment_account_id,
      'code'               => getCode('payment'),
      'name'               => $debt->platform_name . ' - ' . $debt->name . ' (' . $debt->code . ')',
      'amount'             => $debt->disbursement_amount,
      'date'               => $debt->start_date,
      'is_draft'           => true,
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
