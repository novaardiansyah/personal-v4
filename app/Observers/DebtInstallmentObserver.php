<?php

namespace App\Observers;

use App\Models\DebtInstallment;

class DebtInstallmentObserver
{
  public function updated(DebtInstallment $installment): void
  {
    $debt = $installment->debt;
    if ($debt) {
      $allPaid = $debt->installments()->where('status', '!=', 'paid')->count() === 0;
      $newStatus = $allPaid ? 'paid' : 'ongoing';
      if ($debt->status !== $newStatus) {
        $debt->update(['status' => $newStatus]);
      }
    }
    $this->_log('Updated', $installment);
  }

  public function created(DebtInstallment $installment): void
  {
    $this->_log('Created', $installment);
  }

  public function deleted(DebtInstallment $installment): void
  {
    $this->_log('Deleted', $installment);
  }

  private function _log(string $event, DebtInstallment $installment): void
  {
    saveActivityLog([
      'event' => $event,
      'model' => 'DebtInstallment',
      'subject_type' => DebtInstallment::class,
      'subject_id' => $installment->id,
    ], $installment);
  }
}
