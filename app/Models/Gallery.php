<?php

namespace App\Models;

use App\Observers\GalleryObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([GalleryObserver::class])]
class Gallery extends Model
{
  use SoftDeletes;
  protected $guarded = ['id'];
  protected $table = 'galleries';
  protected $casts = [
    'is_publish' => 'boolean',
  ];

  public function tag()
  {
    return $this->belongsTo(GalleryTag::class, 'tag_id');
  }
}
