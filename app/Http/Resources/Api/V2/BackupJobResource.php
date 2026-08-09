<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V2;

use App\Models\BackupJob;
use App\Models\BackupSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BackupJobResource extends JsonResource
{
  public function toArray(Request $request): array
  {
		$filename = BackupSchedule::generateFilename($this->backupSchedule?->filename_pattern, '-' . $this->id . '.zip');

    return [
      'id'                     => $this->id,
      'backup_schedule_id'     => $this->backup_schedule_id,
      'status'                 => $this->status?->getLabel(),
      'message'                => $this->message,
      'started_at'             => $this->started_at?->format('Y-m-d H:i:s'),
      'finished_at'            => $this->finished_at?->format('Y-m-d H:i:s'),
      'type'                   => $this->backupSchedule?->type?->value ?? (string) ($this->backupSchedule?->type ?? 'files'),
      'drivers'                => $this->backupSchedule?->drivers,
      'database_name'          => $this->backupSchedule?->database_name,
      'source_path'            => $this->backupSchedule?->source_path,
      'include'                => $this->backupSchedule?->include ?? [],
      'exclude'                => $this->backupSchedule?->exclude ?? [],
      'destination_path'       => $this->backupSchedule?->local_destination_path,
      'keep_local_backup'      => (bool) ($this->backupSchedule?->keep_local_backup ?? true),
      'is_sync_cloud'          => (bool) ($this->backupSchedule?->is_sync_cloud ?? false),
      'cloud_destination_path' => $this->backupSchedule?->r2_destination_path,
      'expected_filename'      => $filename,
    ];
  }
}
