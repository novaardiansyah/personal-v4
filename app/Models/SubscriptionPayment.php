<?php

namespace App\Models;

use App\Observers\SubscriptionPaymentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([SubscriptionPaymentObserver::class])]
class SubscriptionPayment extends Model
{
  protected $table = 'subscription_payments';

  protected $fillable = [
    'subscription_id',
    'payment_id',
    'period',
  ];

  public function subscription(): BelongsTo
  {
    return $this->belongsTo(Subscription::class, 'subscription_id');
  }

  public function payment(): BelongsTo
  {
    return $this->belongsTo(Payment::class, 'payment_id');
  }
}
