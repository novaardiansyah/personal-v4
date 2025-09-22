<?php

namespace App\Jobs\PaymentResource;

use App\Services\PaymentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PaymentReportPdf implements ShouldQueue
{
  use Queueable;

  /**
   * Create a new job instance.
   */
  public function __construct(public array $data = [])
  {
    //
  }

  /**
   * Execute the job.
   */
  public function handle(): void
  {
    \Log::info('3246 --> PaymentReportPdf: Started.');

    $send = array_merge([
      'filename'     => 'payment-report',
      'title'        => 'Laporan keuangan',
      'notification' => true,
    ], $this->data);

    PaymentService::make_pdf($send);

    \Log::info('3247 --> PaymentReportPdf: Finished.');
  }
}
