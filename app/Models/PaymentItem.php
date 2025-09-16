<?php

namespace App\Models;

use App\Observers\PaymentItemObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy([PaymentItemObserver::class])]
class PaymentItem extends Model
{
  protected $guarded = ['id'];
  protected $table = 'payment_item';
}
