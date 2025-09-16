<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
  use SoftDeletes;
  
  protected $guarded = ['id'];

  protected $casts = [
    'attachments'  => 'array',
    'has_items'    => 'boolean',
    'is_scheduled' => 'boolean',
  ];

  public function payment_account(): BelongsTo
  {
    return $this->belongsTo(PaymentAccount::class, 'payment_account_id');
  }

  public function payment_account_to(): BelongsTo
  {
    return $this->belongsTo(PaymentAccount::class, 'payment_account_to_id');
  }

  public function payment_type(): BelongsTo
  {
    return $this->belongsTo(PaymentType::class, 'type_id');
  }

  public function items(): BelongsToMany
  {
    return $this->belongsToMany(Item::class, 'payment_item')->withPivot(['item_code', 'quantity', 'price', 'total'])->withTimestamps();
  }

  public static function mutateDataPayment(array $data): array
  {
    $data['user_id'] = auth()->id();

    $has_charge         = boolval($data['has_charge'] ?? 0);
    $is_scheduled       = boolval($data['is_scheduled'] ?? 0);
    $type_id            = intval($data['type_id'] ?? 2);
    $amount             = intval($data['amount'] ?? 0);
    $payment_account    = PaymentAccount::find($data['payment_account_id']);
    $payment_account_to = PaymentAccount::find($data['payment_account_to_id'] ?? -1);

    if ($is_scheduled) $has_charge = true;

    if ($type_id == 2) {
      // ! Income
      $payment_account->deposit += $amount;
    } else {
      if (!$has_charge && $payment_account->deposit < $amount) {
        return ['status' => false, 'message' => 'The amount in the payment account is not sufficient for the transaction.', 'data' => $data];
      }

      if ($type_id == 1) {
        // ! Expense
        $payment_account->deposit -= $amount;
      } else if ($type_id == 3 || $type_id == 4) {
        // ! Transfer / Withdrawal
        if (!$payment_account_to) return ['status' => false, 'message' => 'The destination payment account is invalid or not found.', 'data' => $data];
        
        $payment_account->deposit -= $amount;
        $payment_account_to->deposit += $amount;
      } else {
        // ! NO ACTION
        return ['status' => false, 'message' => 'The selected transaction type is invalid.', 'data' => $data];
      }
    }
    
    if (!$has_charge) {
      if ($payment_account->isDirty('deposit')) {
        $payment_account->save();
      }
  
      if ($payment_account_to && $payment_account_to->isDirty('deposit')) {
        $payment_account_to->save();
      }
    }

    $data['code'] = getCode('payment');

    return ['status' => true, 'message' => 'Transaction data has been successfully transferred and saved.', 'data' => $data];
  }

  public static function scheduledPayment(): array
  {
    $today    = Carbon::now()->format('Y-m-d');
    $tomorrow = Carbon::now()->addDay()->format('Y-m-d');

    $scheduledPayments = Payment::with(['payment_account:id,name,deposit', 'payment_account_to:id,name,deposit'])->where('is_scheduled', true)
      ->whereBetween('date', [$today, $tomorrow])
      ->orderBy('type_id', 'desc')
      ->get();

    if ($scheduledPayments->isEmpty()) {
      return ['status' => false, 'message' => 'No scheduled payments found for today.'];
    }

    $scheduledPayments->each(function (Payment $payment) {
      $payment->is_scheduled = false;
      $payment->save();

      if ((int) $payment->type_id === PaymentType::EXPENSE) {
        $payment->payment_account->deposit -= $payment->amount;
      } else if ((int) $payment->type_id === PaymentType::INCOME) {
        $payment->payment_account->deposit += $payment->amount;
      }

      if ($payment->payment_account_to && ((int) $payment->type_id === PaymentType::TRANSFER || (int) $payment->type_id === PaymentType::WITHDRAWAL)) {
        $payment->payment_account->deposit -= $payment->amount;
        $payment->payment_account_to->deposit += $payment->amount;
      }

      if ($payment->payment_account->isDirty()) {
        $payment->payment_account->save();
      }

      if ($payment->payment_account_to && $payment->payment_account_to->isDirty()) {
        $payment->payment_account_to->save();
      }

      if ($payment->billing) {
        $payment->billing->afterSuccessPaid();
      }
    });

    return ['status' => true, 'message' => 'Scheduled payments processed successfully.'];
  }
}
