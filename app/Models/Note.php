<?php

namespace App\Models;

use App\Observers\NoteObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([NoteObserver::class])]
class Note extends Model
{
  use SoftDeletes;

  protected $guarded = ['id'];

  protected $casts = [
    'is_pinned'   => 'boolean',
    'is_archived' => 'boolean',
  ];

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class, 'user_id');
  }
}
