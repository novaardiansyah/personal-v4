<?php

namespace App\Observers;

use App\Models\PaymentAccount;

class PaymentAccountObserver
{
  /**
   * Handle the PaymentAccount "created" event.
   */
  public function created(PaymentAccount $paymentAccount): void
  {
    $this->_log('Created', $paymentAccount);
  }

  /**
   * Handle the PaymentAccount "updated" event.
   */
  public function updated(PaymentAccount $paymentAccount): void
  {
    $this->_log('Updated', $paymentAccount);
  }

  /**
   * Handle the PaymentAccount "deleted" event.
   */
  public function deleted(PaymentAccount $paymentAccount): void
  {
    $this->_log('Deleted', $paymentAccount);
  }

  /**
   * Handle the PaymentAccount "restored" event.
   */
  public function restored(PaymentAccount $paymentAccount): void
  {
    $this->_log('Restored', $paymentAccount);
  }

  /**
   * Handle the PaymentAccount "force deleted" event.
   */
  public function forceDeleted(PaymentAccount $paymentAccount): void
  {
    $this->_log('Force Deleted', $paymentAccount);
  }

  private function _log(string $event, PaymentAccount $paymentAccount): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'Payment Account',
      'subject_type' => PaymentAccount::class,
      'subject_id'   => $paymentAccount->id,
    ], $paymentAccount);
  }
}
