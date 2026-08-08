<?php

namespace App\Models;

use App\Enums\BackupScheduleIntervalUnit;
use App\Enums\BackupType;
use App\Observers\BackupScheduleObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([BackupScheduleObserver::class])]
class BackupSchedule extends Model
{
  use SoftDeletes;

  protected $table = 'backup_schedules';

  protected $fillable = [
    'uid',
    'name',
    'type',
    'drivers',
    'database_name',
    'source_path',
    'exclude',
    'destination_path',
    'local_destination_path',
    'r2_destination_path',
    'keep_local_backup',
    'is_sync_cloud',
    'filename_pattern',
    'count_backup',
    'max_count_backup',
    'interval_value',
    'interval_unit',
    'next_backup_at',
    'last_backup_at',
    'is_enabled',
  ];

  protected $casts = [
    'uid'                    => 'string',
    'name'                   => 'string',
    'type'                   => BackupType::class,
    'drivers'                => 'string',
    'database_name'          => 'string',
    'source_path'            => 'string',
    'exclude'                => 'array',
    'destination_path'       => 'string',
    'local_destination_path' => 'string',
    'r2_destination_path'    => 'string',
    'keep_local_backup'      => 'boolean',
    'is_sync_cloud'          => 'boolean',
    'filename_pattern'       => 'string',
    'count_backup'           => 'integer',
    'max_count_backup'       => 'integer',
    'interval_value'         => 'integer',
    'interval_unit'          => BackupScheduleIntervalUnit::class,
    'next_backup_at'         => 'datetime',
    'last_backup_at'         => 'datetime',
    'is_enabled'             => 'boolean',
    'deleted_at'             => 'datetime',
  ];

  public function getDestinationPathAttribute(): ?string
  {
    return $this->local_destination_path ?? $this->attributes['destination_path'] ?? null;
  }

  public function backupJobs(): HasMany
  {
    return $this->hasMany(BackupJob::class);
  }

  public function backups(): HasManyThrough
  {
    return $this->hasManyThrough(Backup::class, BackupJob::class);
  }

	public static function generateFilename(?string $pattern, ?string $extension = '.zip'): string
	{
		if (empty($pattern)) {
			return '-';
		}

		$preview = preg_replace_callback('/\{([^}]+)\}/', function ($matches) {
			try {
				return now()->format($matches[1]);
			} catch (\Throwable $e) {
				return $matches[0];
			}
		}, $pattern);

		return $preview . $extension;
	}
}
