<?php

namespace App\Models;

use App\Observers\AiAssistantContextObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
  ];

  protected $casts = [
    'temperature' => 'float',
    'max_tokens'  => 'integer',
    'is_active'   => 'boolean',
  ];

  protected $attributes = [
    'default_model' => 'general-chat',
    'temperature'   => 0.30,
    'max_tokens'    => 3072,
    'is_active'     => false,
  ];

  public function scopeActive(Builder $query): void
  {
    $query->where('is_active', true);
  }
}
