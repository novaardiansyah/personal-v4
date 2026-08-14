<?php

namespace App\Models;

use App\Observers\AiAssistantMessageObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([AiAssistantMessageObserver::class])]
class AiAssistantMessage extends Model
{
  use SoftDeletes;

  protected $fillable = [
    'uuid',
    'session_id',
    'user_id',
    'role',
    'content',
    'reasoning_content',
    'token_prompt',
    'token_completion',
    'token_total',
    'latency_ms',
    'model_used',
    'status',
    'error_message',
    'metadata',
  ];

  protected $casts = [
    'token_prompt'     => 'integer',
    'token_completion' => 'integer',
    'token_total'      => 'integer',
    'latency_ms'       => 'integer',
    'metadata'         => 'array',
  ];

  protected $attributes = [
    'role'             => 'user',
    'token_prompt'     => 0,
    'token_completion' => 0,
    'token_total'      => 0,
    'latency_ms'       => 0,
    'status'           => 'processing',
  ];

  public function scopeForSession(Builder $query, int $sessionId): void
  {
    $query->where('session_id', $sessionId);
  }

  public function scopeForUser(Builder $query, int $userId): void
  {
    $query->where('user_id', $userId);
  }

  public function session(): BelongsTo
  {
    return $this->belongsTo(AiAssistantSession::class, 'session_id');
  }

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class, 'user_id');
  }
}
