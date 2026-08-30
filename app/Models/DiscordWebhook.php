<?php

namespace App\Models;

use App\Observers\DiscordWebhookObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([DiscordWebhookObserver::class])]
class DiscordWebhook extends Model
{
  use SoftDeletes;

  protected $table = 'discord_webhooks';

  protected $fillable = [
    'uid',
    'channel_id',
    'name',
    'url',
    'description',
  ];

  public function channel(): BelongsTo
  {
    return $this->belongsTo(DiscordChannel::class, 'channel_id');
  }
}
