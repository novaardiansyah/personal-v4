<?php

namespace App\Models;

use App\Observers\CalendarReminderObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([CalendarReminderObserver::class])]
class CalendarReminder extends Model
{
    protected $table = 'calendar_reminders';

    protected $fillable = [
        'user_id',
        'event_id',
        'todo_id',
        'remind_at',
        'reminded_at',
        'code',
    ];

    protected $casts = [
        'remind_at'   => 'datetime',
        'reminded_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class, 'event_id');
    }

    public function todo(): BelongsTo
    {
        return $this->belongsTo(CalendarTodo::class, 'todo_id');
    }
}