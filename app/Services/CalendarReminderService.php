<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\CalendarReminder;
use App\Models\CalendarTodo;
use Carbon\Carbon;

class CalendarReminderService
{
  const DEFAULT_EVENT_REMINDERS     = [15, 30, 60];
  const DEFAULT_ALL_DAY_REMINDERS   = [1440];
  const DEFAULT_TODO_REMINDERS      = [30];

  public function schedule(CalendarEvent|CalendarTodo $model, int $minutesBefore): CalendarReminder
  {
    $remindAt = $this->calculateReminderTime($model, $minutesBefore);

    $reminderData = [
      'user_id'   => $model->user_id,
      'remind_at' => $remindAt,
    ];

    if ($model instanceof CalendarEvent) {
      $reminderData['event_id'] = $model->id;
    } else {
      $reminderData['todo_id'] = $model->id;
    }

    return CalendarReminder::create($reminderData);
  }

  public function scheduleDefaults(CalendarEvent $event): void
  {
    if ($event->is_all_day) {
      $reminderMinutes = self::DEFAULT_ALL_DAY_REMINDERS;
    } else {
      $reminderMinutes = self::DEFAULT_EVENT_REMINDERS;
    }

    foreach ($reminderMinutes as $minutes) {
      $this->schedule($event, $minutes);
    }
  }

  public function scheduleForTodo(CalendarTodo $todo, int $minutesBefore = null): ?CalendarReminder
  {
    if (!$todo->due_at) {
      return null;
    }

    $minutes = $minutesBefore ?? self::DEFAULT_TODO_REMINDERS[0];
    return $this->schedule($todo, $minutes);
  }

  public function cancelReminders(CalendarEvent|CalendarTodo $model): void
  {
    if ($model instanceof CalendarEvent) {
      CalendarReminder::where('event_id', $model->id)
        ->whereNull('reminded_at')
        ->delete();
    } else {
      CalendarReminder::where('todo_id', $model->id)
        ->whereNull('reminded_at')
        ->delete();
    }
  }

  private function calculateReminderTime(CalendarEvent|CalendarTodo $model, int $minutesBefore): Carbon
  {
    if ($model instanceof CalendarEvent) {
      return $model->start_at->copy()->subMinutes($minutesBefore);
    }

    return $model->due_at->copy()->subMinutes($minutesBefore);
  }
}
