<?php

namespace App\Models;

use App\Observers\PaymentItemObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[ObservedBy(PaymentItemObserver::class)]
class PaymentItem extends Pivot
{
	protected $table = 'payment_item';

	public $incrementing = true;

	protected $guarded = ['id'];

	public function payment(): BelongsTo
	{
		return $this->belongsTo(Payment::class, 'payment_id');
	}

	public function item(): BelongsTo
	{
		return $this->belongsTo(Item::class, 'item_id');
	}
}
