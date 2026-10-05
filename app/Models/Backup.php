<?php

namespace App\Models;

use App\Enums\BackupStatus;
use App\Enums\BackupType;
use App\Observers\BackupObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([BackupObserver::class])]
class Backup extends Model
{
  use SoftDeletes;

  protected $table = 'backups';

  protected $fillable = [
    'storage_id',
    'server_id',
    'schedule_id',
    'uid',
    'file_name',
    'file_path',
    'cloud_file_path',
    'file_size',
    'checksum',
    'type',
    'started_at',
    'completed_at',
    'duration',
    'status',
    'message',
  ];

  protected $casts = [
    'storage_id'      => 'integer',
    'server_id'       => 'integer',
    'schedule_id'     => 'integer',
    'uid'             => 'string',
    'file_name'       => 'string',
    'file_path'       => 'string',
    'cloud_file_path' => 'string',
    'file_size'       => 'integer',
    'checksum'        => 'string',
    'type'            => BackupType::class,
    'started_at'      => 'datetime',
    'completed_at'    => 'datetime',
    'duration'        => 'integer',
    'status'          => BackupStatus::class,
    'message'         => 'string',
    'deleted_at'      => 'datetime',
  ];

  public function storage(): BelongsTo
  {
    return $this->belongsTo(BackupStorage::class, 'storage_id');
  }

  public function server(): BelongsTo
  {
    return $this->belongsTo(BackupStorage::class, 'server_id');
  }

  public function schedule(): BelongsTo
  {
    return $this->belongsTo(BackupSchedule::class, 'schedule_id');
  }
}
