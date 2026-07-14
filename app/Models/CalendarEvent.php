<?php

namespace App\Models;

use App\Observers\CalendarEventObserver;
use App\Services\CalendarRecurrenceService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([CalendarEventObserver::class])]
class CalendarEvent extends Model
{
    use SoftDeletes;

    protected $table = 'calendar_events';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'location',
        'start_at',
        'end_at',
        'is_all_day',
        'category_id',
        'color',
        'recurrence_type',
        'recurrence_interval',
        'recurrence_end_at',
        'recurring_event_id',
        'source_type',
        'source_id',
        'metadata',
        'code',
    ];

    protected $casts = [
        'start_at'            => 'datetime',
        'end_at'              => 'datetime',
        'is_all_day'          => 'boolean',
        'recurrence_interval' => 'integer',
        'recurrence_end_at'   => 'datetime',
        'metadata'            => 'json',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CalendarCategory::class, 'category_id');
    }

    public function source(): MorphTo
    {
        return $this->morphTo('source', 'source_type', 'source_id');
    }

    public function todos(): HasMany
    {
        return $this->hasMany(CalendarTodo::class, 'event_id');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(CalendarReminder::class, 'event_id');
    }

    public function recurringEvent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'recurring_event_id');
    }

    public function recurringEvents(): HasMany
    {
        return $this->hasMany(self::class, 'recurring_event_id');
    }

    public function scopeGetEventsInRange(Builder $query, Carbon $start, Carbon $end)
    {
      $service = new CalendarRecurrenceService();

      $nonRecurringEvents = $query
        ->whereNull('recurrence_type')
        ->whereBetween('start_at', [$start, $end])
        ->with(['category'])
        ->get();

      $recurringParents = $query
        ->whereNotNull('recurrence_type')
        ->whereNull('recurring_event_id')
        ->with(['category'])
        ->get();

      $expandedInstances = collect();

      foreach ($recurringParents as $parent) {
        $instances = $service->expand($parent, $start, $end);
        $expandedInstances = $expandedInstances->merge($instances);
      }

      $allEvents = $nonRecurringEvents->merge($expandedInstances);

      return $allEvents->sortBy(function ($event) {
        $startKey = $event instanceof self ? $event->start_at : $event->instance_start;
        return $startKey->timestamp;
      });
    }

    public function generateInstances(Carbon $start, Carbon $end)
    {
      $service = new CalendarRecurrenceService();
      return $service->generateInstances($this, $start, $end);
    }
}