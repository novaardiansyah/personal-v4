<?php

namespace App\Observers;

use App\Models\PaymentGoal;

class PaymentGoalObserver
{
  public function creating(PaymentGoal $paymentGoal)
  {
    $paymentGoal->code = getCode('payment_goals');
    $paymentGoal->user_id = auth()->id();
  }

  /**
   * Handle the PaymentGoal "created" event.
   */
  public function created(PaymentGoal $paymentGoal): void
  {
    $this->_log('Created', $paymentGoal);
  }

  /**
   * Handle the PaymentGoal "updated" event.
   */
  public function updated(PaymentGoal $paymentGoal): void
  {
    $this->_log('Updated', $paymentGoal);
  }

  /**
   * Handle the PaymentGoal "deleted" event.
   */
  public function deleted(PaymentGoal $paymentGoal): void
  {
    $this->_log('Deleted', $paymentGoal);
  }

  /**
   * Handle the PaymentGoal "restored" event.
   */
  public function restored(PaymentGoal $paymentGoal): void
  {
    $this->_log('Restored', $paymentGoal);
  }

  /**
   * Handle the PaymentGoal "force deleted" event.
   */
  public function forceDeleted(PaymentGoal $paymentGoal): void
  {
    $this->_log('Force Deleted', $paymentGoal);
  }

  private function _log(string $event, PaymentGoal $paymentGoal): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'Payment Goal',
      'subject_type' => PaymentGoal::class,
      'subject_id'   => $paymentGoal->id,
    ], $paymentGoal);
  }
}