<?php

namespace App\Models;

use App\Observers\DebtInstallmentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([DebtInstallmentObserver::class])]
class DebtInstallment extends Model
{
  protected $table = 'debt_installments';

  protected $fillable = [
    'debt_id',
    'payment_id',
    'installment_number',
    'due_date',
    'principal_amount',
    'interest_amount',
    'service_fee',
    'vat_amount',
    'penalty_amount',
    'total_amount',
    'status',
    'paid_at',
  ];

  protected $casts = [
    'due_date' => 'date',
    'paid_at' => 'datetime',
  ];

  public function debt(): BelongsTo
  {
    return $this->belongsTo(Debt::class, 'debt_id');
  }

  public function payment(): BelongsTo
  {
    return $this->belongsTo(Payment::class, 'payment_id');
  }
}
