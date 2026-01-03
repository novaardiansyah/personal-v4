<?php

namespace App\Models;

use App\Enums\EmailStatus;
use App\Observers\EmailObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([EmailObserver::class])]
class Email extends Model
{
  use SoftDeletes;
  protected $table = 'emails';
  protected $fillable = ['uid', 'name', 'email', 'subject', 'message', 'status', 'is_url_attachment'];
  protected $appends = ['url_attachment'];
  protected $casts = [
    'status' => EmailStatus::class,
    'is_url_attachment' => 'boolean',
  ];

  public function files(): MorphMany
  {
    return $this->morphMany(File::class, 'subject');
  }

  protected function UrlAttachment(): Attribute
  {
    $url = getSetting('portfolio_url');
    return Attribute::make(
      get: fn (): string => $this->is_url_attachment ? $url . '/files/d/' . $this->uid : '',
    );
  }
}
