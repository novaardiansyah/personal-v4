<?php

namespace App\Services;

use App\Models\CalendarEvent;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CalendarRecurrenceService
{
  public function expand(CalendarEvent $event, Carbon $start, Carbon $end): Collection
  {
    $instances = collect();

    if (!$event->recurrence_type) {
      return $instances;
    }

    $current = $this->getStartDate($event, $start);
    $recurrenceEnd = $event->recurrence_end_at ?? $end->copy()->addYears(2);

    while ($current <= $end && $current <= $recurrenceEnd) {
      if ($current >= $start) {
        $override = $event->recurringEvents()
          ->where('start_at', '>=', $current->startOfDay())
          ->where('start_at', '<', $current->copy()->addDay()->startOfDay())
          ->first();

        if ($override) {
          $instances->push($this->createInstance($override, $override->start_at, $override->end_at));
        } else {
          $instanceEnd = $this->calculateInstanceEnd($event, $current);
          $instances->push($this->createInstance($event, $current, $instanceEnd));
        }
      }

      $current = $this->getNextOccurrence($event, $current);

      if ($current > $recurrenceEnd) {
        break;
      }
    }

    return $instances;
  }

  public function generateInstances(CalendarEvent $event, Carbon $start, Carbon $end): Collection
  {
    return $this->expand($event, $start, $end);
  }

  private function getStartDate(CalendarEvent $event, Carbon $rangeStart): Carbon
  {
    $eventStart = $event->start_at;

    if ($eventStart > $rangeStart) {
      return $eventStart->copy();
    }

    return match ($event->recurrence_type) {
      'daily'   => $rangeStart->copy(),
      'weekly'  => $this->getWeeklyStart($event, $rangeStart),
      'monthly' => $this->getMonthlyStart($event, $rangeStart),
      'yearly'  => $this->getYearlyStart($event, $rangeStart),
      default   => $rangeStart->copy(),
    };
  }

  private function getWeeklyStart(CalendarEvent $event, Carbon $rangeStart): Carbon
  {
    $eventDayOfWeek = $event->start_at->dayOfWeek;
    $current = $rangeStart->copy()->startOfDay();

    while ($current->dayOfWeek !== $eventDayOfWeek) {
      $current->addDay();
    }

    return $current;
  }

  private function getMonthlyStart(CalendarEvent $event, Carbon $rangeStart): Carbon
  {
    $eventDay = $event->start_at->day;
    $current = $rangeStart->copy()->startOfMonth();

    $targetDay = min($eventDay, $current->daysInMonth);
    $current->setDay($targetDay);

    if ($current < $rangeStart) {
      $current->addMonth();
      $targetDay = min($eventDay, $current->daysInMonth);
      $current->setDay($targetDay);
    }

    return $current;
  }

  private function getYearlyStart(CalendarEvent $event, Carbon $rangeStart): Carbon
  {
    $eventMonth = $event->start_at->month;
    $eventDay   = $event->start_at->day;
    $current = $rangeStart->copy()->startOfYear();

    $current->setMonth($eventMonth);
    $current->setDay(min($eventDay, $current->daysInMonth));

    if ($current < $rangeStart) {
      $current->addYear();
      $current->setDay(min($eventDay, $current->daysInMonth));
    }

    return $current;
  }

  private function getNextOccurrence(CalendarEvent $event, Carbon $current): Carbon
  {
    $next = $current->copy();
    $interval = $event->recurrence_interval ?? 1;

    return match ($event->recurrence_type) {
      'daily'   => $next->addDays($interval),
      'weekly'  => $next->addWeeks($interval),
      'monthly' => $this->addMonthsWithDayClamp($next, $interval, $event->start_at->day),
      'yearly'  => $next->addYears($interval),
      default   => $next,
    };
  }

  private function addMonthsWithDayClamp(Carbon $date, int $months, int $originalDay): Carbon
  {
    $date->addMonths($months);
    $maxDay = $date->daysInMonth;

    if ($originalDay > $maxDay) {
      $date->setDay($maxDay);
    } else {
      $date->setDay($originalDay);
    }

    return $date;
  }

  private function calculateInstanceEnd(CalendarEvent $event, Carbon $instanceStart): ?Carbon
  {
    if (!$event->end_at) {
      return null;
    }

    $duration = $event->start_at->diffInSeconds($event->end_at);

    return $instanceStart->copy()->addSeconds($duration);
  }

  private function createInstance(CalendarEvent $event, Carbon $startAt, ?Carbon $endAt): object
  {
    return (object) [
      'id'                   => $event->id,
      'code'                 => $event->code,
      'title'                => $event->title,
      'description'          => $event->description,
      'location'             => $event->location,
      'instance_start'       => $startAt,
      'instance_end'         => $endAt,
      'start_at'             => $event->start_at,
      'end_at'               => $event->end_at,
      'is_all_day'           => $event->is_all_day,
      'category_id'          => $event->category_id,
      'color'                => $event->color,
      'recurrence_type'      => $event->recurrence_type,
      'recurrence_interval'  => $event->recurrence_interval,
      'recurrence_end_at'    => $event->recurrence_end_at,
      'recurring_event_id'   => $event->recurring_event_id,
      'source_type'          => $event->source_type,
      'source_id'            => $event->source_id,
      'metadata'             => $event->metadata,
      'category'             => $event->category,
    ];
  }
}
