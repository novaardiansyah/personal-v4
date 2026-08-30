<?php

namespace App\Models;

use App\Observers\DiscordChannelObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([DiscordChannelObserver::class])]
class DiscordChannel extends Model
{
  use SoftDeletes;

  protected $table = 'discord_channels';

  protected $fillable = [
    'uid',
    'server_id',
    'name',
    'channel_id',
    'description',
  ];

  public function server(): BelongsTo
  {
    return $this->belongsTo(DiscordServer::class, 'server_id');
  }

  public function webhooks(): HasMany
  {
    return $this->hasMany(DiscordWebhook::class, 'channel_id');
  }
}
