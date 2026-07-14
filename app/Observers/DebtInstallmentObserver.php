<?php

namespace App\Observers;

use App\Models\DebtInstallment;
use App\Services\DebtResource\DebtService;
use App\Services\CalendarIntegrationService;

class DebtInstallmentObserver
{
  public function updated(DebtInstallment $installment): void
  {
    $debt = $installment->debt;
    if ($debt) {
      $newStatus = DebtService::calculateStatus($debt);
      if ($debt->status !== $newStatus) {
        $debt->update(['status' => $newStatus]);
      }
    }
    if ($installment->isDirty('due_date') || $installment->isDirty('paid_at')) {
      (new CalendarIntegrationService())->syncFromDebtInstallment($installment);
    }
    $this->_log('Updated', $installment);
  }

  public function created(DebtInstallment $installment): void
  {
    (new CalendarIntegrationService())->syncFromDebtInstallment($installment);
    $this->_log('Created', $installment);
  }

  public function deleted(DebtInstallment $installment): void
  {
    (new CalendarIntegrationService())->removeSource('debt', $installment->id);
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
