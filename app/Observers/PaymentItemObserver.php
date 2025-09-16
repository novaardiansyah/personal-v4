<?php

namespace App\Observers;

use App\Models\PaymentItem;

class PaymentItemObserver
{
  /**
   * Handle the PaymentItem "created" event.
   */
  public function created(PaymentItem $paymentItem): void
  {
    $this->_log('Created', $paymentItem);
  }

  /**
   * Handle the PaymentItem "updated" event.
   */
  public function updated(PaymentItem $paymentItem): void
  {
    $this->_log('Updated', $paymentItem);
  }

  /**
   * Handle the PaymentItem "deleted" event.
   */
  public function deleted(PaymentItem $paymentItem): void
  {
    $this->_log('Deleted', $paymentItem);
  }

  /**
   * Handle the PaymentItem "restored" event.
   */
  public function restored(PaymentItem $paymentItem): void
  {
    $this->_log('Restored', $paymentItem);
  }

  /**
   * Handle the PaymentItem "force deleted" event.
   */
  public function forceDeleted(PaymentItem $paymentItem): void
  {
    $this->_log('Force Deleted', $paymentItem);
  }
  
  private function _log(string $event, PaymentItem $paymentItem): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'PaymentItem',
      'subject_type' => PaymentItem::class,
      'subject_id'   => $paymentItem->id,
    ], $paymentItem);
  }
}
