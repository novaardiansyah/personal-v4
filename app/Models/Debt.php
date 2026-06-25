<?php

namespace App\Models;

use App\Observers\DebtObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([DebtObserver::class])]
class Debt extends Model
{
  use SoftDeletes;

  protected $table = 'debts';

  protected $fillable = [
    'user_id',
    'payment_account_id',
    'code',
    'platform_name',
    'name',
    'principal_amount',
    'admin_fee',
    'disbursement_amount',
    'interest_rate',
    'service_fee_rate',
    'tenor',
    'start_date',
    'status',
    'description',
  ];

  protected $casts = [
    'start_date' => 'date',
    'interest_rate' => 'decimal:5',
    'service_fee_rate' => 'decimal:5',
  ];

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  public function payment_account(): BelongsTo
  {
    return $this->belongsTo(PaymentAccount::class, 'payment_account_id');
  }

  public function installments(): HasMany
  {
    return $this->hasMany(DebtInstallment::class, 'debt_id');
  }
}
