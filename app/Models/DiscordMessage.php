<?php

namespace App\Models;

use App\Enums\DiscordMessageStatus;
use App\Observers\DiscordMessageObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([DiscordMessageObserver::class])]
class DiscordMessage extends Model
{
  use SoftDeletes;

  protected $table = 'discord_messages';

  protected $fillable = [
    'uid',
    'webhook_id',
    'content',
    'status',
    'count_retry',
    'response',
  ];

  protected $casts = [
    'uid'         => 'string',
    'webhook_id'  => 'integer',
    'content'     => 'array',
    'status'      => DiscordMessageStatus::class,
    'count_retry' => 'integer',
    'response'    => 'array',
    'deleted_at'  => 'datetime',
  ];

  protected $attributes = [
    'status'      => DiscordMessageStatus::Pending,
    'count_retry' => 0,
  ];

  public function webhook(): BelongsTo
  {
    return $this->belongsTo(DiscordWebhook::class, 'webhook_id');
  }
}
