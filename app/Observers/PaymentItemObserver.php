<?php

namespace App\Observers;

use App\Models\PaymentItem;

class PaymentItemObserver
{
  public function created(PaymentItem $paymentItem): void
  {
    $this->_log('Created', $paymentItem);
  }

  public function updated(PaymentItem $paymentItem): void
  {
    $this->_log('Updated', $paymentItem);
  }

  public function deleted(PaymentItem $paymentItem): void
  {
    $this->_log('Deleted', $paymentItem);
  }

  public function restored(PaymentItem $paymentItem): void
  {
    $this->_log('Restored', $paymentItem);
  }

  public function forceDeleted(PaymentItem $paymentItem): void
  {
    $this->_log('Force Deleted', $paymentItem);
  }

  private function _log(string $event, PaymentItem $paymentItem): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'Payment Item',
      'subject_type' => PaymentItem::class,
      'subject_id'   => $paymentItem->id,
    ], $paymentItem);
  }
}
