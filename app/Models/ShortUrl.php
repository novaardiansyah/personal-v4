<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy(\App\Observers\ShortUrlObserver::class)]
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

  public function getCleanShortCode(): string 
  {
    $value = $this->short_code;
    $domain = getSetting('short_url_domain');
    return str_replace($domain . '/', '', $value);
  }
}
