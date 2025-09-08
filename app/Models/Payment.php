<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
}
