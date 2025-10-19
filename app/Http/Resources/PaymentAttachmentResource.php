<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PaymentAttachmentResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    return [
      'id'             => $this->id,
      'url'            => $this->url,
      'filepath'       => $this->filepath,
      'filename'       => $this->filename,
      'extension'      => $this->extension,
      'formatted_size' => $this->formatFileSize($this->size),
    ];
  }

  /**
   * Format file size to human readable format
   */
  private function formatFileSize($size): string
  {
    if (!$size) return '0 Bytes';

    $units = ['Bytes', 'KB', 'MB', 'GB', 'TB'];

    if ($size === 0) return '0 Bytes';

    $base = log($size, 1024);

    return round(pow(1024, $base - floor($base)), 2) . ' ' . $units[floor($base)];
  }
}