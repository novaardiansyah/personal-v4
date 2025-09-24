<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShortUrl extends Model
{
  use SoftDeletes;
  protected $guarded = ['id'];
  protected $table = 'short_urls';
  protected $casts = [
    'is_active' => 'boolean'
  ];

  public function getShortCodeAttribute($value)
  {
    $domain = getSetting('short_url_domain');
    return $domain ? $domain . '/' . $value : $value;
  }
}
