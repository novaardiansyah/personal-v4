<?php

namespace App\Jobs\GalleryResource;

use App\Services\GalleryResource\CdnService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ForceDeleteGalleryJob implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public function __construct(
    protected string|int $id
  ) {
  }

  public function handle(CdnService $service): void
  {
    $service->forceDelete($this->id);
  }
}
