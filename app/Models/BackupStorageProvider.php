<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\BackupStorageProviderObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([BackupStorageProviderObserver::class])]
class BackupStorageProvider extends Model
{
  use SoftDeletes;

  protected $table = 'backup_storage_providers';

  protected $fillable = [
    'uid',
    'name',
    'slug',
  ];

  protected $casts = [
    'uid'  => 'string',
    'name' => 'string',
    'slug' => 'string',
  ];
}
