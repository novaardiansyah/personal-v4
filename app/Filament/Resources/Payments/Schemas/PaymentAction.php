<?php

namespace App\Filament\Resources\Settings\Schemas;

use App\Models\Payment;
use App\Models\PaymentType;
use Illuminate\Support\Facades\Storage;

class PaymentAction
{
  public static function deleteAfter(Payment $record)
  {
    $attachments  = $record->attachments;
    $is_scheduled = $record->is_scheduled ?? false;

    if (PaymentType::TRANSFER == $record->type_id || PaymentType::WITHDRAWAL == $record->type_id)
    {
      $balanceOrigin = $record->payment_account->deposit + $record->amount;
      $balanceTo     = $record->payment_account_to - $record->amount;

      if (!$is_scheduled) {
        $record->payment_account->update([
          'deposit' => $balanceOrigin
        ]);

        $record->payment_account_to->update([
          'deposit' => $balanceTo
        ]);
      }
    } else if (PaymentType::EXPENSE == $record->type_id || PaymentType::INCOME == $record->type_id) {
      $adjustment    = ($record->type_id == PaymentType::EXPENSE) ? +$record->amount : -$record->amount;
      $depositChange = ($record->payment_account->deposit + $adjustment);

      if (!$is_scheduled) {
        $record->payment_account->update([
          'deposit' => $depositChange
        ]);
      }
    }

    // ! Has attachments
    if (!empty($attachments)) {
      foreach ($attachments as $attachment) {
        // ! Doesnt exist
        if (!Storage::disk('public')->exists($attachment))
          continue;

        // ! Delete attachment
        Storage::disk('public')->delete($attachment);
      }
    }
  }
}