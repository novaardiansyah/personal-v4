<?php

namespace App\Models;

use App\Enums\BackupScheduleIntervalUnit;
use App\Observers\BackupScheduleObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([BackupScheduleObserver::class])]
class BackupSchedule extends Model
{
  use SoftDeletes;

  protected $table = 'backup_schedules';

  protected $fillable = [
    'uid',
    'source_path',
    'destination_path',
    'filename_pattern',
    'retention_days',
    'interval_value',
    'interval_unit',
    'next_backup_at',
    'last_backup_at',
    'is_enabled',
  ];

  protected $casts = [
    'uid'              => 'string',
    'source_path'      => 'string',
    'destination_path' => 'string',
    'filename_pattern' => 'string',
    'retention_days'   => 'integer',
    'interval_value'   => 'integer',
    'interval_unit'    => BackupScheduleIntervalUnit::class,
    'next_backup_at'   => 'datetime',
    'last_backup_at'   => 'datetime',
    'is_enabled'       => 'boolean',
    'deleted_at'       => 'datetime',
  ];

  public function backupJobs(): HasMany
  {
    return $this->hasMany(BackupJob::class);
  }
}
