<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use App\Observers\PushNotificationObserver;

#[ObservedBy([PushNotificationObserver::class])]
class PushNotification extends Model
{
  use SoftDeletes;

  protected $guarded = ['id'];

  protected $casts = [
    'data'          => 'array',
    'response_data' => 'array',
    'sent_at'       => 'datetime',
  ];

  public function user()
  {
    return $this->belongsTo(User::class);
  }
}
