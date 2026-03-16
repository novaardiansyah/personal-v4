<?php

namespace App\Observers;

use App\Models\PaymentType;

class PaymentTypeObserver
{
	public function creating(PaymentType $paymentType): void
	{
		$paymentType->uid = uuid7();
	}

	public function updating(PaymentType $paymentType): void
	{
		if (!$paymentType->uid) {
			$paymentType->uid = uuid7();
		}
	}

  public function created(PaymentType $paymentType): void
  {
    $this->_log('Created', $paymentType);
  }

  public function updated(PaymentType $paymentType): void
  {
    $this->_log('Updated', $paymentType);
  }

  public function deleted(PaymentType $paymentType): void
  {
    $this->_log('Deleted', $paymentType);
  }

  public function restored(PaymentType $paymentType): void
  {
    $this->_log('Restored', $paymentType);
  }

  public function forceDeleted(PaymentType $paymentType): void
  {
    $this->_log('Force Deleted', $paymentType);
  }

  private function _log(string $event, PaymentType $paymentType): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'Payment Type',
      'subject_type' => PaymentType::class,
      'subject_id'   => $paymentType->id,
    ], $paymentType);
  }
}
