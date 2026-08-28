<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\BackupStorageObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([BackupStorageObserver::class])]
class BackupStorage extends Model
{
  use SoftDeletes;

  protected $table = 'backup_storages';

  protected $fillable = [ 'uid', 'name', 'slug', 'keys', 'active', 'provider_id' ];

  protected $casts = [
    'keys'   => 'array',
    'active' => 'boolean',
  ];

  public function provider(): BelongsTo
  {
    return $this->belongsTo(BackupStorageProvider::class, 'provider_id');
  }
}
