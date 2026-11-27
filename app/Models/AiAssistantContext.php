<?php

namespace App\Models;

use App\Observers\AiAssistantContextObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([AiAssistantContextObserver::class])]
class AiAssistantContext extends Model
{
  use SoftDeletes;

  protected $fillable = [
    'uuid',
    'name',
    'description',
    'system_prompt',
    'default_model',
    'temperature',
    'max_tokens',
    'is_active',
    'allowed_tools',
    'starter_prompts',
    'sort_order',
  ];

  protected $casts = [
    'temperature'     => 'float',
    'max_tokens'      => 'integer',
    'is_active'       => 'boolean',
    'allowed_tools'   => 'array',
    'starter_prompts' => 'array',
    'sort_order'      => 'integer',
  ];

  protected $attributes = [
    'default_model' => 'general-chat',
    'temperature'   => 0.30,
    'max_tokens'    => 3072,
    'is_active'     => false,
    'sort_order'    => 0,
  ];

  public function scopeActive(Builder $query): void
  {
    $query->where('is_active', true);
  }

  public function scopeOrdered(Builder $query): void
  {
    $query->orderBy('sort_order')->orderBy('id');
  }

  public function sessions(): HasMany
  {
    return $this->hasMany(AiAssistantSession::class, 'context_id');
  }
}
