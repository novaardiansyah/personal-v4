<?php

/*
 * Project Name: personal-v4
 * File: PaymentAccountObserver.php
 * Created Date: Thursday December 11th 2025
 *
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 *
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

declare(strict_types=1);

namespace App\Observers;

use App\Models\PaymentAccount;
use Illuminate\Support\Facades\Storage;

class PaymentAccountObserver
{
	public function saving(PaymentAccount $paymentAccount): void
	{
		if (!$paymentAccount->user_id) {
			$paymentAccount->user_id = getUser()->id;
		}
	}

	/**
	 * Handle the PaymentAccount "created" event.
	 */
	public function created(PaymentAccount $paymentAccount): void
	{
		$this->_log('Created', $paymentAccount);
	}

	/**
	 * Handle the PaymentAccount "updated" event.
	 */
	public function updated(PaymentAccount $paymentAccount): void
	{
		$previous   = $paymentAccount->getOriginal('deposit');
		$current    = $paymentAccount->deposit;
		$difference = $current - $previous;

		$data = [
			'prev_properties' => [
				'difference' => $difference,
			],
			'properties' => [
				'difference' => $difference,
			],
		];

		$this->_log('Updated', $paymentAccount, $data);
	}


	/**
	 * Handle the PaymentAccount "deleted" event.
	 */
	public function deleted(PaymentAccount $paymentAccount): void
	{
		$this->_log('Deleted', $paymentAccount);
		$this->_delete_local_image($paymentAccount);
	}

	/**
	 * Handle the PaymentAccount "restored" event.
	 */
	public function restored(PaymentAccount $paymentAccount): void
	{
		$this->_log('Restored', $paymentAccount);
	}

	/**
	 * Handle the PaymentAccount "force deleted" event.
	 */
	public function forceDeleted(PaymentAccount $paymentAccount): void
	{
		$this->_log('Force Deleted', $paymentAccount);
	}

	private function _delete_local_image(PaymentAccount $paymentAccount): void
	{
		if ($paymentAccount->logo) {
			Storage::disk('public')->delete($paymentAccount->logo);
		}
	}

	private function _log(string $event, PaymentAccount $paymentAccount, array $data = []): void
	{
		saveActivityLog(array_merge([
			'event'        => $event,
			'model'        => 'Payment Account',
			'subject_type' => PaymentAccount::class,
			'subject_id'   => $paymentAccount->id,
		], $data), $paymentAccount);
	}
}
