<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BackupResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'              => $this->id,
      'uid'             => $this->uid,
      'backup_job_id'   => $this->backup_job_id,
      'file_name'       => $this->file_name,
      'file_path'       => $this->file_path,
      'cloud_file_path' => $this->cloud_file_path,
      'file_size'       => $this->file_size,
      'checksum'        => $this->checksum,
      'type'            => $this->type?->value ?? $this->type,
      'type_label'      => $this->type?->getLabel(),
      'started_at'      => $this->started_at?->format('Y-m-d H:i:s'),
      'completed_at'    => $this->completed_at?->format('Y-m-d H:i:s'),
      'duration'        => $this->duration,
      'status'          => $this->status?->value ?? $this->status,
      'status_label'    => $this->status?->getLabel(),
      'message'         => $this->message,
      'server_name'     => $this->server_name,
      'created_at'      => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at'      => $this->updated_at?->format('Y-m-d H:i:s'),
      'deleted_at'      => $this->deleted_at?->format('Y-m-d H:i:s'),
    ];
  }
}
