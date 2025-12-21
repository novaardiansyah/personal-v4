<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlogSubscriber extends Model
{
  use SoftDeletes;
  protected $table = 'blog_subscribers';
  protected $fillable = ['email', 'name', 'token', 'subscribed_at', 'unsubscribed_at', 'verified_at'];
  protected $casts = [
    'subscribed_at' => 'datetime',
    'unsubscribed_at' => 'datetime',
    'verified_at' => 'datetime',
  ];
}
