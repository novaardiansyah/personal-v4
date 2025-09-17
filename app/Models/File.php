<?php

namespace App\Models;

use App\Observers\FileObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([FileObserver::class])]
class File extends Model
{
  use SoftDeletes;

  protected $guarded = ['id'];  
  protected $table = 'files';
  protected $casts = [
    'has_been_deleted'        => 'boolean',
    'scheduled_deletion_time' => 'datetime'
  ];

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class, 'user_id');
  }
}
