<?php

namespace App\Observers;

use App\Models\Subscription;
use Illuminate\Support\Facades\DB;

class SubscriptionObserver
{
  public function creating(Subscription $subscription): void
  {
    DB::transaction(function () use ($subscription) {
      $subscription->user_id = auth()->id();
      $subscription->code    = getCode('subscription');
    });
  }

  public function created(Subscription $subscription): void
  {
    $this->_log('Created', $subscription);
  }

  public function updated(Subscription $subscription): void
  {
    $this->_log('Updated', $subscription);
  }

  private function _log(string $event, Subscription $subscription): void
  {
    saveActivityLog([
      'event'       => $event,
      'model'       => 'Subscription',
      'subject_type' => Subscription::class,
      'subject_id'   => $subscription->id,
    ], $subscription);
  }
}