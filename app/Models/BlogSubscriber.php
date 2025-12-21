<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlogSubscriber extends Model
{
  use SoftDeletes;
  protected $table = 'blog_subscribers';
  protected $guarded = ['id'];
  protected $casts = [
    'subscribed_at' => 'datetime',
    'unsubscribed_at' => 'datetime',
    'verified_at' => 'datetime',
  ];
}
