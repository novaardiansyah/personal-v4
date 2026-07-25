<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class BackupResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    $downloadUrl = null;
    if ($this->file_path && Storage::disk('public')->exists($this->file_path)) {
      $downloadUrl = Storage::disk('public')->url($this->file_path);
    }

    return [
      'id'           => $this->id,
      'uid'          => $this->uid,
      'file_name'    => $this->file_name,
      'file_path'    => $this->file_path,
      'file_size'    => $this->file_size,
      'checksum'     => $this->checksum,
      'type'         => $this->type?->value ?? $this->type,
      'type_label'   => $this->type?->getLabel(),
      'started_at'   => $this->started_at?->format('Y-m-d H:i:s'),
      'completed_at' => $this->completed_at?->format('Y-m-d H:i:s'),
      'duration'     => $this->duration,
      'status'       => $this->status?->value ?? $this->status,
      'status_label' => $this->status?->getLabel(),
      'message'      => $this->message,
      'server_name'  => $this->server_name,
      'download_url' => $downloadUrl,
      'created_at'   => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at'   => $this->updated_at?->format('Y-m-d H:i:s'),
      'deleted_at'   => $this->deleted_at?->format('Y-m-d H:i:s'),
    ];
  }
}
