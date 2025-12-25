<?php

namespace App\Models;

use App\Observers\GalleryObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([GalleryObserver::class])]
class Gallery extends Model
{
  use SoftDeletes;
  protected $table = 'galleries';

  protected $fillable = ['user_id', 'subject_id', 'subject_type', 'file_path', 'file_name', 'file_size', 'is_private', 'has_optimized', 'description'];

  protected $casts = [
    'is_private'    => 'boolean',
    'file_size'     => 'integer',
    'has_optimized' => 'boolean',
  ];

  public function user()
  {
    return $this->belongsTo(User::class);
  }

  public function subject(): MorphTo
  {
    return $this->morphTo();
  }
}
