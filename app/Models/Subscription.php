<?php

namespace App\Models;

use App\Observers\SubscriptionObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([SubscriptionObserver::class])]
class Subscription extends Model
{
  use SoftDeletes;

  protected $fillable = [
    'user_id',
    'code',
    'name',
    'amount',
    'payment_account_id',
    'category_id',
    'cycle',
    'next_date',
    'reminder_days_before',
    'is_paused',
    'last_reminded_at',
  ];

  protected $casts = [
    'amount'               => 'integer',
    'is_paused'            => 'boolean',
    'reminder_days_before' => 'integer',
    'next_date'            => 'date',
    'last_reminded_at'     => 'datetime',
  ];

  protected $with = ['payment_account', 'category'];

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  public function payment_account(): BelongsTo
  {
    $user_id = auth()->id() ?? null;
    return $this->belongsTo(PaymentAccount::class, 'payment_account_id')
      ->when($user_id, fn ($query) => $query->where('user_id', $user_id));
  }

  public function category(): BelongsTo
  {
    return $this->belongsTo(PaymentCategory::class, 'category_id');
  }
}