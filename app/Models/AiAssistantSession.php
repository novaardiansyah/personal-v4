<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder;

class AiAssistantSession extends Model
{
	protected $fillable = [
		'uuid',
		'user_id',
		'title',
		'total_tokens_used',
		'last_interacted_at',
	];

	protected $casts = [
		'last_interacted_at' => 'datetime',
	];

	protected $attributes = [
		'title'              => 'New Conversation',
		'total_tokens_used'  => 0,
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
}
