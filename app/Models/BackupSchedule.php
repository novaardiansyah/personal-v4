<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BackupScheduleIntervalUnit;
use App\Enums\BackupType;
use App\Observers\BackupScheduleObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([BackupScheduleObserver::class])]
class BackupSchedule extends Model
{
  use SoftDeletes;

  protected $table = 'backup_schedules';

  protected $fillable = [
    'uid',
    'storage_id',
    'server_id',
    'name',
    'type',
    'drivers',
    'database_name',
    'source_path',
    'include',
    'exclude',
    'destination_path',
    'local_destination_path',
    'r2_destination_path',
    'keep_local_backup',
    'is_sync_cloud',
    'filename_pattern',
    'count_backup',
    'max_count_backup',
    'sum_file_size',
    'interval_value',
    'interval_unit',
    'next_backup_at',
    'last_backup_at',
    'is_enabled',
  ];

  protected $casts = [
    'uid'                    => 'string',
    'storage_id'             => 'integer',
    'server_id'              => 'integer',
    'name'                   => 'string',
    'type'                   => BackupType::class,
    'drivers'                => 'string',
    'database_name'          => 'string',
    'source_path'            => 'string',
    'include'                => 'array',
    'exclude'                => 'array',
    'destination_path'       => 'string',
    'local_destination_path' => 'string',
    'r2_destination_path'    => 'string',
    'keep_local_backup'      => 'boolean',
    'is_sync_cloud'          => 'boolean',
    'filename_pattern'       => 'string',
    'count_backup'           => 'integer',
    'max_count_backup'       => 'integer',
    'sum_file_size'          => 'integer',
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

  public function storage(): BelongsTo
  {
    return $this->belongsTo(BackupStorage::class, 'storage_id');
  }

  public function server(): BelongsTo
  {
    return $this->belongsTo(BackupStorage::class, 'server_id');
  }

  public function backups(): HasMany
  {
    return $this->hasMany(Backup::class, 'schedule_id');
  }

  public static function parsePattern(?string $pattern): ?string
  {
    if (empty($pattern)) {
      return $pattern;
    }

    return preg_replace_callback('/\{([^}]+)\}/', function ($matches) {
      try {
        return now()->format($matches[1]);
      } catch (\Throwable $e) {
        return $matches[0];
      }
    }, $pattern);
  }

  public static function generateFilename(?string $pattern, ?string $extension = '.zip'): string
  {
    if (empty($pattern)) {
      return '-';
    }

    return (static::parsePattern($pattern) ?? '') . $extension;
  }

  public static function generateDestinationPath(?string $path): ?string
  {
    if (empty($path)) {
      return $path;
    }

    return static::parsePattern($path);
  }
}
