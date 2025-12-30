<?php

namespace App\Jobs\GalleryResource;

use App\Services\GalleryResource\CdnService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class UploadGalleryJob implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public function __construct(
    protected string $filePath,
    protected ?string $description = null,
    protected bool $isPrivate = false
  ) {
  }

  public function handle(CdnService $service): void
  {
    $service->upload($this->filePath, $this->description, $this->isPrivate);

    Storage::disk('public')->delete($this->filePath);
  }
}
