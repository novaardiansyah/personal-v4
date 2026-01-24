<?php

namespace App\Observers;

use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Models\PaymentType;
use App\Services\AttachmentService;
use App\Services\PaymentResource\PaymentService;
use Illuminate\Validation\ValidationException;

class PaymentObserver
{
  public function creating(Payment $payment): void
  {
    $payment->code = getCode('payment');
    $payment->user_id = auth()->id();

    $record = $payment;

    $is_draft = $record->is_draft;
    $is_scheduled = $record->is_scheduled;

    if ($is_draft || $is_scheduled) {
      return;
    }

    $type_id = intval($record->type_id);
    $amount = intval($record->amount);

    $insufficientBalanceError = [
      'data.payment_account_id' => ['Insufficient account balance.'],
      'data.amount' => ['The amount exceeds the account balance.'],
    ];

    $incomeOrExpense = $type_id == PaymentType::EXPENSE || $type_id == PaymentType::INCOME;
    $transferOrWithdrawal = $type_id == PaymentType::TRANSFER || $type_id == PaymentType::WITHDRAWAL;

    if ($incomeOrExpense) {
      $depositChange = $record->payment_account->deposit;

      if ($type_id == PaymentType::EXPENSE) {
        if ($depositChange < $amount) {
          throw ValidationException::withMessages($insufficientBalanceError);
        }
        $depositChange -= $amount;
      } else {
        $depositChange += $amount;
      }

      $record->payment_account->update([
        'deposit' => $depositChange
      ]);
    } else if ($transferOrWithdrawal) {
      $balanceOrigin = $record->payment_account->deposit;
      $balanceTo = $record->payment_account_to->deposit;

      if ($balanceOrigin < $amount) {
        throw ValidationException::withMessages($insufficientBalanceError);
      }

      $record->payment_account->update([
        'deposit' => $balanceOrigin - $amount
      ]);

      $record->payment_account_to->update([
        'deposit' => $balanceTo + $amount
      ]);
    }
  }

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

  public function updating(Payment $payment): void
  {
    $record = $payment;
    $oldValue = [];

    $changes = collect($record->getDirty())->except($record->getHidden());
    $oldValue = $changes->mapWithKeys(fn($value, $key) => [$key => $record->getOriginal($key)])->toArray();

    $is_draft = $record->is_draft;
    $is_scheduled = $record->is_scheduled;

    if ($is_draft || $is_scheduled) {
      return;
    }

    $insufficientBalanceError = [
      'data.payment_account_id' => ['Insufficient account balance.'],
      'data.amount' => ['The amount exceeds the account balance.'],
    ];

    $typeChanged = $record->isDirty('type_id');
    $amountChanged = $record->isDirty('amount');
    $accountChanged = $record->isDirty('payment_account_id');
    $accountToChanged = $record->isDirty('payment_account_to_id');

    if ($accountChanged || $accountToChanged) {
      $type_id = intval($record->type_id);
      $amount = intval($record->amount);

      $incomeOrExpense = $type_id == PaymentType::EXPENSE || $type_id == PaymentType::INCOME;
      $transferOrWithdrawal = $type_id == PaymentType::TRANSFER || $type_id == PaymentType::WITHDRAWAL;

      if ($accountChanged) {
        $oldAccountId = intval($oldValue['payment_account_id']);
        $oldAccount = PaymentAccount::find($oldAccountId);

        if ($oldAccount) {
          if ($incomeOrExpense) {
            $revertAdjustment = ($type_id == PaymentType::EXPENSE) ? +$amount : -$amount;
            $oldAccount->update([
              'deposit' => $oldAccount->deposit + $revertAdjustment
            ]);
          } else if ($transferOrWithdrawal) {
            $oldAccount->update([
              'deposit' => $oldAccount->deposit + $amount
            ]);
          }
        }

        $record->payment_account->refresh();
        $depositChange = $record->payment_account->deposit;

        if ($incomeOrExpense) {
          if ($type_id == PaymentType::EXPENSE) {
            if ($depositChange < $amount) {
              throw ValidationException::withMessages($insufficientBalanceError);
            }
            $depositChange -= $amount;
          } else {
            $depositChange += $amount;
          }

          $record->payment_account->update([
            'deposit' => $depositChange
          ]);
        } else if ($transferOrWithdrawal) {
          if ($depositChange < $amount) {
            throw ValidationException::withMessages($insufficientBalanceError);
          }

          $record->payment_account->update([
            'deposit' => $depositChange - $amount
          ]);
        }
      }

      if ($accountToChanged && $transferOrWithdrawal) {
        $oldAccountToId = intval($oldValue['payment_account_to_id']);
        $oldAccountTo = PaymentAccount::find($oldAccountToId);

        if ($oldAccountTo) {
          $oldAccountTo->update([
            'deposit' => $oldAccountTo->deposit - $amount
          ]);
        }

        $record->payment_account_to->refresh();
        $record->payment_account_to->update([
          'deposit' => $record->payment_account_to->deposit + $amount
        ]);
      }

      return;
    }

    if ($typeChanged) {
      $oldTypeId = intval($oldValue['type_id']);
      $newTypeId = intval($record->type_id);
      $oldAmount = intval($oldValue['amount'] ?? $record->amount);
      $newAmount = intval($record->amount);

      $oldIncomeOrExpense = $oldTypeId == PaymentType::EXPENSE || $oldTypeId == PaymentType::INCOME;
      $oldTransferOrWithdrawal = $oldTypeId == PaymentType::TRANSFER || $oldTypeId == PaymentType::WITHDRAWAL;

      if ($oldIncomeOrExpense) {
        $revertAdjustment = ($oldTypeId == PaymentType::EXPENSE) ? +$oldAmount : -$oldAmount;
        $record->payment_account->update([
          'deposit' => $record->payment_account->deposit + $revertAdjustment
        ]);
        $record->payment_account->refresh();
      } else if ($oldTransferOrWithdrawal) {
        $record->payment_account->update([
          'deposit' => $record->payment_account->deposit + $oldAmount
        ]);
        $record->payment_account_to->update([
          'deposit' => $record->payment_account_to->deposit - $oldAmount
        ]);
        $record->payment_account->refresh();
        $record->payment_account_to->refresh();
      }

      $newIncomeOrExpense = $newTypeId == PaymentType::EXPENSE || $newTypeId == PaymentType::INCOME;
      $newTransferOrWithdrawal = $newTypeId == PaymentType::TRANSFER || $newTypeId == PaymentType::WITHDRAWAL;

      if ($newIncomeOrExpense) {
        $depositChange = $record->payment_account->deposit;

        if ($newTypeId == PaymentType::EXPENSE) {
          if ($depositChange < $newAmount) {
            throw ValidationException::withMessages($insufficientBalanceError);
          }
          $depositChange -= $newAmount;
        } else {
          $depositChange += $newAmount;
        }

        $record->payment_account->update([
          'deposit' => $depositChange
        ]);
      } else if ($newTransferOrWithdrawal) {
        $balanceOrigin = $record->payment_account->deposit;
        $balanceTo = $record->payment_account_to->deposit;

        if ($balanceOrigin < $newAmount) {
          throw ValidationException::withMessages($insufficientBalanceError);
        }

        $record->payment_account->update([
          'deposit' => $balanceOrigin - $newAmount
        ]);

        $record->payment_account_to->update([
          'deposit' => $balanceTo + $newAmount
        ]);
      }

      return;
    }

    if ($amountChanged) {
      $oldAmount = intval($oldValue['amount']);
      $amount = intval($record->amount);
      $type_id = intval($record->type_id);

      $incomeOrExpense = $type_id == PaymentType::EXPENSE || $type_id == PaymentType::INCOME;
      $transferOrWithdrawal = $type_id == PaymentType::TRANSFER || $type_id == PaymentType::WITHDRAWAL;

      if ($incomeOrExpense) {
        $adjustment = ($type_id == PaymentType::EXPENSE) ? +$oldAmount : -$oldAmount;
        $depositChange = ($record->payment_account->deposit + $adjustment);

        if ($depositChange < $amount && $depositChange != 0) {
          throw ValidationException::withMessages($insufficientBalanceError);
        }

        if ($type_id == PaymentType::EXPENSE) {
          $amount = -$amount;
        }

        $depositChange += $amount;

        $record->payment_account->update([
          'deposit' => $depositChange
        ]);
      } else if ($transferOrWithdrawal) {
        $balanceTo = $record->payment_account_to->deposit + intval($record->amount ?? $oldAmount) - $oldAmount;
        $balanceOrigin = $record->payment_account->deposit + $oldAmount;

        if ($balanceOrigin < $record->amount) {
          throw ValidationException::withMessages($insufficientBalanceError);
        }

        $record->payment_account->update([
          'deposit' => $balanceOrigin - $record->amount
        ]);

        $record->payment_account_to->update([
          'deposit' => $balanceTo
        ]);
      }

      return;
    }
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
    $attachments = $payment->attachments;
    $is_draft = $payment->is_draft;
    $is_scheduled = $payment->is_scheduled;

    if (!$is_draft && !$is_scheduled) {
      $type_id = intval($payment->type_id);
      $amount = intval($payment->amount);

      if ($type_id == PaymentType::EXPENSE || $type_id == PaymentType::INCOME) {
        $adjustment = ($type_id == PaymentType::EXPENSE) ? +$amount : -$amount;

        $payment->payment_account->update([
          'deposit' => $payment->payment_account->deposit + $adjustment
        ]);
      } else if ($type_id == PaymentType::TRANSFER || $type_id == PaymentType::WITHDRAWAL) {
        $payment->payment_account->update([
          'deposit' => $payment->payment_account->deposit + $amount
        ]);

        $payment->payment_account_to->update([
          'deposit' => $payment->payment_account_to->deposit - $amount
        ]);
      }
    }

    if (!empty($attachments)) {
      AttachmentService::deleteAttachmentFiles($attachments);
    }

    $payment->items()->detach();
  }

  private function _log(string $event, Payment $payment): void
  {
    saveActivityLog([
      'event' => $event,
      'model' => 'Payment',
      'subject_type' => Payment::class,
      'subject_id' => $payment->id,
    ], $payment);
  }
}
