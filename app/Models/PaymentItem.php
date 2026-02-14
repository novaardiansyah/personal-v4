<?php

namespace App\Models;

use App\Observers\PaymentItemObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[ObservedBy(PaymentItemObserver::class)]
class PaymentItem extends Pivot
{
	protected $table = 'payment_item';

	public $incrementing = true;

	protected $guarded = ['id'];
}
