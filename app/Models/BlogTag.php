<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlogTag extends Model
{
  use SoftDeletes;
  protected $table = 'blog_tags';
  protected $guarded = ['id'];
}
