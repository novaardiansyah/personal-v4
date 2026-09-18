<?php

namespace App\Models;

use App\Observers\SubscriptionObserver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

  public function subscription_payments(): HasMany
  {
    return $this->hasMany(SubscriptionPayment::class, 'subscription_id');
  }

  public function getPeriodForDate(Carbon|string|null $date = null): string
  {
    $parsed = Carbon::parse($date ?? $this->next_date ?? now());

    return match ($this->cycle) {
      'yearly'    => $parsed->format('Y'),
      'quarterly' => 'Q' . $parsed->quarter . '-' . $parsed->format('Y'),
      default     => $parsed->format('m-Y'),
    };
  }

  public function hasDraftPaymentForPeriod(string $period): bool
  {
    return $this->subscription_payments()
      ->where('period', $period)
      ->whereHas('payment', fn ($query) => $query->whereNull('deleted_at'))
      ->exists();
  }
}