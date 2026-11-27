<?php

namespace App\Services\AiAssistant\Tools;

use App\Models\CalendarTodo;

class GetCalendarTodosTool implements AiAssistantToolInterface
{
  public function getName(): string
  {
    return 'get_calendar_todos';
  }

  public function getDescription(): string
  {
    return 'Retrieve user calendar todos filtered by completion status (pending, completed, overdue, or all) and limit.';
  }

  public function getParametersSchema(): array
  {
    return [
      'type'       => 'object',
      'properties' => [
        'status' => [
          'type'        => 'string',
          'enum'        => ['pending', 'completed', 'overdue', 'all'],
          'description' => 'Todo status filter (default: pending).',
        ],
        'limit'  => [
          'type'        => 'integer',
          'description' => 'Max todos to return (default 15, max 30).',
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

    $status = strtolower($arguments['status'] ?? 'pending');
    $query  = CalendarTodo::where('user_id', $userId)->with('event');

    if ($status === 'pending') {
      $query->whereNull('completed_at');
    } elseif ($status === 'completed') {
      $query->whereNotNull('completed_at');
    } elseif ($status === 'overdue') {
      $query->whereNull('completed_at')->where('due_at', '<', now());
    }

    $limit   = min((int) ($arguments['limit'] ?? 15), 30);
    $records = $query->orderBy('due_at')->orderBy('sort_order')->take($limit)->get();

    $todos = $records->map(function ($todo) {
      return [
        'code'         => $todo->code,
        'title'        => $todo->title,
        'description'  => $todo->description,
        'priority'     => $todo->priority?->value ?? $todo->priority,
        'due_at'       => $todo->due_at?->format('Y-m-d H:i'),
        'completed_at' => $todo->completed_at?->format('Y-m-d H:i'),
        'event_title'  => $todo->event?->title,
      ];
    })->toArray();

    return [
      'status_filter' => $status,
      'count'         => count($todos),
      'todos'         => $todos,
    ];
  }
}
