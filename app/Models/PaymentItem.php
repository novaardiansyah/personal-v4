<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
