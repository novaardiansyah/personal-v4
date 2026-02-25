<?php

namespace App\Jobs\PaymentGoalResource;

use App\Services\PaymentGoalResource\PaymentGoalService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class PaymentGoalReportExcelJob implements ShouldQueue
{
	use Queueable;

	public function __construct(public array $data = []) {}

	public function handle(): void
	{
		Log::info('4202 --> PaymentGoalReportExcelJob: Started.');

		(new PaymentGoalService())->generateReportExcel($this->data);

		Log::info('4203 --> PaymentGoalReportExcelJob: Finished.');
	}
}
