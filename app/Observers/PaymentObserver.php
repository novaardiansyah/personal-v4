<?php

namespace App\Observers;

use App\Models\Payment;

class PaymentObserver
{
  /**
   * Handle the Payment "created" event.
   */
  public function created(Payment $payment): void
  {
    $this->_log('Created', $payment);
  }

  /**
   * Handle the Payment "updated" event.
   */
  public function updated(Payment $payment): void
  {
    $this->_log('Updated', $payment);
  }

  /**
   * Handle the Payment "deleted" event.
   */
  public function deleted(Payment $payment): void
  {
    $this->_log('Deleted', $payment);
  }

  /**
   * Handle the Payment "restored" event.
   */
  public function restored(Payment $payment): void
  {
    $this->_log('Restored', $payment);
  }

  /**
   * Handle the Payment "force deleted" event.
   */
  public function forceDeleted(Payment $payment): void
  {
    $this->_log('Force Deleted', $payment);
  }

  private function _log(string $event, Payment $payment): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'Payment',
      'subject_type' => Payment::class,
      'subject_id'   => $payment->id,
    ], $payment);
  }
}
