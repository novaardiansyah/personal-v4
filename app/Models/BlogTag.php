<?php

namespace App\Models;

use App\Observers\BlogTagObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([BlogTagObserver::class])]
class BlogTag extends Model
{
  use SoftDeletes;
  protected $table = 'blog_tags';
  protected $guarded = ['id'];
}
