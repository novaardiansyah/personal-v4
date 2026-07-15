<?php

namespace App\Livewire;

use App\Models\CalendarEvent;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\Attributes\Url;

class CalendarWidget extends Component
{
  #[Url]
  public ?string $month = null;

  public Carbon $currentMonth;
  public array $daysInMonth = [];
  public array $eventsByDate = [];

  public function mount(): void
  {
    if ($this->month) {
      $this->currentMonth = Carbon::createFromFormat('Y-m', $this->month);
    } else {
      $this->currentMonth = now();
    }

    $this->loadEvents();
  }

  public function previousMonth(): void
  {
    $this->currentMonth = $this->currentMonth->copy()->subMonth();
    $this->month = $this->currentMonth->format('Y-m');
    $this->loadEvents();
  }

  public function nextMonth(): void
  {
    $this->currentMonth = $this->currentMonth->copy()->addMonth();
    $this->month = $this->currentMonth->format('Y-m');
    $this->loadEvents();
  }

  public function today(): void
  {
    $this->currentMonth = now();
    $this->month = $this->currentMonth->format('Y-m');
    $this->loadEvents();
  }

  private function loadEvents(): void
  {
    $startOfMonth = $this->currentMonth->copy()->startOfMonth();
    $endOfMonth   = $this->currentMonth->copy()->endOfMonth();

    $events = CalendarEvent::getEventsInRange($startOfMonth, $endOfMonth);

    $this->eventsByDate = [];
    foreach ($events as $event) {
      $dateKey = $event instanceof \stdClass
        ? $event->instance_start->format('Y-m-d')
        : $event->start_at->format('Y-m-d');

      if (!isset($this->eventsByDate[$dateKey])) {
        $this->eventsByDate[$dateKey] = [];
      }

      $this->eventsByDate[$dateKey][] = $event;
    }

    $this->generateCalendarDays();
  }

  private function generateCalendarDays(): void
  {
    $startOfMonth = $this->currentMonth->copy()->startOfMonth();
    $endOfMonth   = $this->currentMonth->copy()->endOfMonth();
    $startDate    = $startOfMonth->copy()->startOfWeek();

    $this->daysInMonth = [];
    $current = $startDate->copy();

    while ($current <= $endOfMonth || $current->weekOfYear === $endOfMonth->weekOfYear) {
      $dateStr = $current->format('Y-m-d');
      $isCurrentMonth = $current->format('Y-m') === $this->currentMonth->format('Y-m');
      $isToday = $current->format('Y-m-d') === now()->format('Y-m-d');

      $this->daysInMonth[] = [
        'date'              => $current->copy(),
        'dateStr'           => $dateStr,
        'day'               => $current->day,
        'isCurrentMonth'    => $isCurrentMonth,
        'isToday'           => $isToday,
        'isWeekend'         => $current->isWeekend(),
        'events'            => $this->eventsByDate[$dateStr] ?? [],
        'eventCount'        => count($this->eventsByDate[$dateStr] ?? []),
      ];

      $current->addDay();

      if (count($this->daysInMonth) % 7 === 0 && !$isCurrentMonth) {
        break;
      }
    }
  }

  public function render()
  {
    return view('livewire.calendar-widget');
  }
}
