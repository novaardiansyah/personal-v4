<?php

namespace App\Models;

use App\Observers\PaymentItemObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([PaymentItemObserver::class])]
class PaymentItem extends Model
{
  protected $guarded = ['id'];
  protected $table = 'payment_item';

  public function payment(): BelongsTo
  {
    return $this->belongsTo(Payment::class);
  }

  public function item(): BelongsTo
  {
    return $this->belongsTo(Item::class);
  }
}
