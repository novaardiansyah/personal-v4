<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlogCategory extends Model
{
  use SoftDeletes;
  protected $table = 'blog_categories';
  protected $guarded = ['id'];
}
