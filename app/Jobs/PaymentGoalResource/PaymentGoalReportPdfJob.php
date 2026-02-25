<?php

/*
 * Project Name: personal-v4
 * File: PaymentGoalReportPdfJob.php
 * Created Date: Tuesday February 24th 2026
 *
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 *
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

declare(strict_types=1);

namespace App\Jobs\PaymentGoalResource;

use App\Services\PaymentGoalResource\PaymentGoalService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class PaymentGoalReportPdfJob implements ShouldQueue
{
	use Queueable;

	public function __construct(public array $data = []) {}

	public function handle(): void
	{
		Log::info('4200 --> PaymentGoalReportPdfJob: Started.');

		(new PaymentGoalService())->generateReportPdf($this->data);

		Log::info('4201 --> PaymentGoalReportPdfJob: Finished.');
	}
}
