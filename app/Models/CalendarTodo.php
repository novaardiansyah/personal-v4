<?php

namespace App\Models;

use App\Observers\CalendarTodoObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([CalendarTodoObserver::class])]
class CalendarTodo extends Model
{
    use SoftDeletes;

    protected $table = 'calendar_todos';

    protected $fillable = [
        'user_id',
        'event_id',
        'title',
        'description',
        'priority',
        'due_at',
        'completed_at',
        'sort_order',
        'code',
    ];

    protected $casts = [
        'due_at'       => 'datetime',
        'completed_at' => 'datetime',
        'priority'     => \App\Enums\TodoPriority::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class, 'event_id');
    }
}