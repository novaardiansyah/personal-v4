<?php

/*
 * Project Name: personal-v4
 * File: PaymentCategoryObserver.php
 * Created Date: Monday March 9th 2026
 * 
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 * 
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

declare(strict_types=1);

namespace App\Observers;

use App\Models\PaymentCategory;

class PaymentCategoryObserver
{
	public function creating(PaymentCategory $paymentCategory): void
	{
		if (!$paymentCategory->user_id) {
			$paymentCategory->user_id = getUser();
		}
	}

	public function created(PaymentCategory $paymentCategory): void
	{
		$this->_log('Created', $paymentCategory);
	}

	public function updated(PaymentCategory $paymentCategory): void
	{
		$this->_log('Updated', $paymentCategory);
	}

	public function deleted(PaymentCategory $paymentCategory): void
	{
		$this->_log('Deleted', $paymentCategory);
	}

	public function restored(PaymentCategory $paymentCategory): void
	{
		$this->_log('Restored', $paymentCategory);
	}

	public function forceDeleted(PaymentCategory $paymentCategory): void
	{
		$this->_log('Force Deleted', $paymentCategory);
	}

	private function _log(string $event, PaymentCategory $paymentCategory): void
	{
		saveActivityLog([
			'event'        => $event,
			'model'        => 'Payment Category',
			'subject_type' => PaymentCategory::class,
			'subject_id'   => $paymentCategory->id,
		], $paymentCategory);
	}
}
