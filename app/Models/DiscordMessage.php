<?php

namespace App\Models;

use App\Enums\DiscordMessageStatus;
use App\Observers\DiscordMessageObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([DiscordMessageObserver::class])]
class DiscordMessage extends Model
{
  use SoftDeletes;

  protected $table = 'discord_messages';

  protected $fillable = [
    'uid',
    'content',
    'status',
    'response',
  ];

  protected $casts = [
    'uid'        => 'string',
    'content'    => 'string',
    'status'     => DiscordMessageStatus::class,
    'response'   => 'string',
    'deleted_at' => 'datetime',
  ];

  protected $attributes = [
    'status' => DiscordMessageStatus::Pending,
  ];
}
