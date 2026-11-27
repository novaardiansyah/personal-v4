<?php

namespace App\Services\AiAssistant\Tools;

use App\Models\CalendarEvent;
use Illuminate\Support\Carbon;

class CreateCalendarEventTool implements AiAssistantToolInterface
{
  public function getName(): string
  {
    return 'create_calendar_event';
  }

  public function getDescription(): string
  {
    return 'Propose scheduling a new calendar event. Requires user confirmation before saving.';
  }

  public function getParametersSchema(): array
  {
    return [
      'type'       => 'object',
      'properties' => [
        'title'       => [
          'type'        => 'string',
          'description' => 'Event title.',
        ],
        'start_at'    => [
          'type'        => 'string',
          'description' => 'Start date/time (YYYY-MM-DD HH:MM).',
        ],
        'end_at'      => [
          'type'        => 'string',
          'description' => 'Optional end date/time (YYYY-MM-DD HH:MM). Defaults to 1 hour after start_at.',
        ],
        'location'    => [
          'type'        => 'string',
          'description' => 'Optional event location.',
        ],
        'description' => [
          'type'        => 'string',
          'description' => 'Optional event description.',
        ],
        'is_all_day'  => [
          'type'        => 'boolean',
          'description' => 'Whether this is an all-day event.',
        ],
      ],
      'required'   => ['title', 'start_at'],
    ];
  }

  public function execute(array $arguments, ?int $userId = null): mixed
  {
    $user   = getUser($userId);
    $userId = $user?->id;

    if (!$userId) {
      return ['status' => false, 'message' => 'User not authenticated'];
    }

    $title   = trim((string) ($arguments['title'] ?? ''));
    $startAt = $arguments['start_at'] ?? '';
    if ($title === '' || $startAt === '') {
      return ['status' => false, 'message' => 'Event title and start_at are required.'];
    }

    $parsedStart = Carbon::parse($startAt)->format('Y-m-d H:i');

    return [
      'status'                => true,
      'requires_confirmation' => true,
      'summary'               => "Schedule event: \"{$title}\" at {$parsedStart}",
      'proposed_data'         => $arguments,
    ];
  }

  public function executeConfirmed(array $arguments, ?int $userId = null): array
  {
    $user   = getUser($userId);
    $userId = $user?->id;

    if (!$userId) {
      return ['status' => false, 'message' => 'User not authenticated'];
    }

    $startAt = Carbon::parse($arguments['start_at']);
    $endAt   = !empty($arguments['end_at']) ? Carbon::parse($arguments['end_at']) : $startAt->copy()->addHour();

    $event = CalendarEvent::create([
      'user_id'     => $userId,
      'title'       => trim((string) $arguments['title']),
      'description' => $arguments['description'] ?? null,
      'location'    => $arguments['location'] ?? null,
      'start_at'    => $startAt,
      'end_at'      => $endAt,
      'is_all_day'  => (bool) ($arguments['is_all_day'] ?? false),
    ]);

    return [
      'status'  => true,
      'message' => "Calendar event {$event->code} ({$event->title}) scheduled.",
      'record'  => [
        'id'       => $event->id,
        'code'     => $event->code,
        'title'    => $event->title,
        'start_at' => $event->start_at?->format('Y-m-d H:i'),
      ],
    ];
  }
}
