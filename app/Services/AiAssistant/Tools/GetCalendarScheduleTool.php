<?php

namespace App\Services\AiAssistant\Tools;

use App\Models\CalendarEvent;
use Illuminate\Support\Carbon;

class GetCalendarScheduleTool implements AiAssistantToolInterface
{
  public function getName(): string
  {
    return 'get_calendar_schedule';
  }

  public function getDescription(): string
  {
    return 'Retrieve calendar events within a date range (defaults to today through 7 days ahead).';
  }

  public function getParametersSchema(): array
  {
    return [
      'type'       => 'object',
      'properties' => [
        'start_date' => [
          'type'        => 'string',
          'description' => 'Start date (YYYY-MM-DD). Defaults to today.',
        ],
        'end_date'   => [
          'type'        => 'string',
          'description' => 'End date (YYYY-MM-DD). Defaults to 7 days from start_date.',
        ],
      ],
      'required'   => [],
    ];
  }

  public function execute(array $arguments, ?int $userId = null): mixed
  {
    $user   = getUser($userId);
    $userId = $user?->id;

    if (!$userId) {
      return ['status' => false, 'message' => 'User not authenticated'];
    }

    $start = !empty($arguments['start_date'])
      ? Carbon::parse($arguments['start_date'])->startOfDay()
      : now()->startOfDay();

    $end = !empty($arguments['end_date'])
      ? Carbon::parse($arguments['end_date'])->endOfDay()
      : $start->copy()->addDays(7)->endOfDay();

    $events = CalendarEvent::where('user_id', $userId)
      ->getEventsInRange($start, $end);

    $results = $events->take(25)->map(function ($ev) {
      $startAt = $ev instanceof CalendarEvent ? $ev->start_at : $ev->instance_start;
      $endAt   = $ev instanceof CalendarEvent ? $ev->end_at : $ev->instance_end;

      return [
        'code'       => $ev->code ?? null,
        'title'      => $ev->title,
        'category'   => $ev->category?->name ?? '-',
        'location'   => $ev->location ?? '-',
        'is_all_day' => (bool) $ev->is_all_day,
        'start_at'   => $startAt?->format('Y-m-d H:i'),
        'end_at'     => $endAt?->format('Y-m-d H:i'),
      ];
    })->values()->toArray();

    return [
      'range'  => $start->format('Y-m-d') . ' to ' . $end->format('Y-m-d'),
      'count'  => count($results),
      'events' => $results,
    ];
  }
}
