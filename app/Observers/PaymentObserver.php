<?php

namespace App\Observers;

use App\Models\Payment;
use App\Models\PaymentType;
use Illuminate\Support\Facades\Storage;

class PaymentObserver
{
  /**
   * Handle the Payment "created" event.
   */
  public function created(Payment $payment): void
  {
    $this->_log('Created', $payment);
  }

  /**
   * Handle the Payment "updated" event.
   */
  public function updated(Payment $payment): void
  {
    $this->_log('Updated', $payment);
  }

  /**
   * Handle the Payment "deleted" event.
   */
  public function deleted(Payment $payment): void
  {
    $this->_handleDeleteLogic($payment);
    $this->_log('Deleted', $payment);
  }

  /**
   * Handle the Payment "restored" event.
   */
  public function restored(Payment $payment): void
  {
    $this->_log('Restored', $payment);
  }

  /**
   * Handle the Payment "force deleted" event.
   */
  public function forceDeleted(Payment $payment): void
  {
    $this->_handleDeleteLogic($payment);
    $this->_log('Force Deleted', $payment);
  }

  /**
   * Handle the delete logic for payment
   */
  private function _handleDeleteLogic(Payment $payment): void
  {
    $attachments  = $payment->attachments;
    $is_scheduled = $payment->is_scheduled ?? false;

    if (PaymentType::TRANSFER == $payment->type_id || PaymentType::WITHDRAWAL == $payment->type_id)
    {
      $balanceOrigin = $payment->payment_account->deposit + $payment->amount;
      $balanceTo     = $payment->payment_account_to - $payment->amount;

      if (!$is_scheduled) {
        $payment->payment_account->update([
          'deposit' => $balanceOrigin
        ]);

        $payment->payment_account_to->update([
          'deposit' => $balanceTo
        ]);
      }
    } else if (PaymentType::EXPENSE == $payment->type_id || PaymentType::INCOME == $payment->type_id) {
      $adjustment    = ($payment->type_id == PaymentType::EXPENSE) ? +$payment->amount : -$payment->amount;
      $depositChange = ($payment->payment_account->deposit + $adjustment);

      if (!$is_scheduled) {
        $payment->payment_account->update([
          'deposit' => $depositChange
        ]);
      }
    }

    // Delete attachments
    if (!empty($attachments)) {
      foreach ($attachments as $attachment) {
        if (Storage::disk('public')->exists($attachment)) {
          Storage::disk('public')->delete($attachment);
        }
      }
    }

    // Delete payment items if any
    $payment->items()->detach();
  }

  private function _log(string $event, Payment $payment): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'Payment',
      'subject_type' => Payment::class,
      'subject_id'   => $payment->id,
    ], $payment);
  }
}
