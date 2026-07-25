<?php

namespace App\Models;

use App\Enums\BackupJobStatus;
use App\Observers\BackupJobObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([BackupJobObserver::class])]
class BackupJob extends Model
{
  use SoftDeletes;

  protected $table = 'backup_jobs';

  protected $fillable = [
    'backup_schedule_id',
    'status',
    'assigned_at',
    'started_at',
    'finished_at',
    'message',
  ];

  protected $casts = [
    'backup_schedule_id' => 'integer',
    'status'             => BackupJobStatus::class,
    'assigned_at'        => 'datetime',
    'started_at'         => 'datetime',
    'finished_at'        => 'datetime',
    'message'            => 'string',
    'deleted_at'         => 'datetime',
  ];

  public function backupSchedule(): BelongsTo
  {
    return $this->belongsTo(BackupSchedule::class);
  }
}
