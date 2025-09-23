<?php

namespace App\Models;

use App\Observers\GalleryTagObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([GalleryTagObserver::class])]
class GalleryTag extends Model
{
  use SoftDeletes;
  protected $table = 'gallery_tags';
  protected $guarded = ['id'];
}
