<?php

namespace App\Models;

use App\Enums\BackupStatus;
use App\Enums\BackupType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Backup extends Model
{
  use SoftDeletes;

  protected $table = 'backups';

  protected $fillable = [
    'uid',
    'file_name',
    'file_path',
    'file_size',
    'checksum',
    'type',
    'started_at',
    'completed_at',
    'duration',
    'status',
    'message',
    'server_name',
  ];

  protected $casts = [
    'uid'          => 'string',
    'file_name'    => 'string',
    'file_path'    => 'string',
    'file_size'    => 'string',
    'checksum'     => 'string',
    'type'         => BackupType::class,
    'started_at'   => 'datetime',
    'completed_at' => 'datetime',
    'duration'     => 'integer',
    'status'       => BackupStatus::class,
    'message'      => 'string',
    'server_name'  => 'string',
    'deleted_at'   => 'datetime',
  ];
}
