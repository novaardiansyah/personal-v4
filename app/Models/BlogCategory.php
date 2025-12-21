<?php

namespace App\Models;

use App\Observers\BlogCategoryObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([BlogCategoryObserver::class])]
class BlogCategory extends Model
{
  use SoftDeletes;
  protected $table = 'blog_categories';
  protected $guarded = ['id'];
}
