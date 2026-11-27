<?php

namespace App\Models;

use App\Observers\AiAssistantSessionObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([AiAssistantSessionObserver::class])]
class AiAssistantSession extends Model
{
  use SoftDeletes;

  protected $fillable = [
    'uuid',
    'user_id',
    'context_id',
    'title',
    'model_override',
    'is_pinned',
    'total_tokens_used',
    'total_cost',
    'summary',
    'last_interacted_at',
  ];

  protected $casts = [
    'is_pinned'          => 'boolean',
    'total_tokens_used'  => 'integer',
    'total_cost'         => 'float',
    'last_interacted_at' => 'datetime',
  ];

  protected $attributes = [
    'title'              => 'New Conversation',
    'is_pinned'          => false,
    'total_tokens_used'  => 0,
    'total_cost'         => 0,
    'last_interacted_at' => null,
  ];

  public function scopeForUser(Builder $query, int $userId): void
  {
    $query->where('user_id', $userId);
  }

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  public function context(): BelongsTo
  {
    return $this->belongsTo(AiAssistantContext::class, 'context_id');
  }

  public function messages(): HasMany
  {
    return $this->hasMany(AiAssistantMessage::class, 'session_id');
  }
}
