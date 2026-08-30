<?php

namespace App\Models;

use App\Observers\DiscordServerObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([DiscordServerObserver::class])]
class DiscordServer extends Model
{
  use SoftDeletes;

  protected $table = 'discord_servers';

  protected $fillable = [
    'name',
    'server_id',
    'server_icon',
    'description',
    'is_active',
  ];

  protected $casts = [
    'is_active' => 'boolean',
  ];

  public function channels(): HasMany
  {
    return $this->hasMany(DiscordChannel::class, 'server_id');
  }
}
