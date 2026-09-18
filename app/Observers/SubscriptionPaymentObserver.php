<?php

namespace App\Observers;

use App\Models\SubscriptionPayment;

class SubscriptionPaymentObserver
{
  public function created(SubscriptionPayment $subscriptionPayment): void
  {
    $this->_log('Created', $subscriptionPayment);
  }

  public function updated(SubscriptionPayment $subscriptionPayment): void
  {
    $this->_log('Updated', $subscriptionPayment);
  }

  public function deleted(SubscriptionPayment $subscriptionPayment): void
  {
    $this->_log('Deleted', $subscriptionPayment);
  }

  private function _log(string $event, SubscriptionPayment $subscriptionPayment): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'SubscriptionPayment',
      'subject_type' => SubscriptionPayment::class,
      'subject_id'   => $subscriptionPayment->id,
    ], $subscriptionPayment);
  }
}
