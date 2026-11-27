<?php

namespace App\Services\AiAssistant\Tools;

use App\Enums\TodoPriority;
use App\Models\CalendarTodo;
use Illuminate\Support\Carbon;

class CreateCalendarTodoTool implements AiAssistantToolInterface
{
  public function getName(): string
  {
    return 'create_calendar_todo';
  }

  public function getDescription(): string
  {
    return 'Propose creating a new calendar todo item. Requires user confirmation before saving.';
  }

  public function getParametersSchema(): array
  {
    return [
      'type'       => 'object',
      'properties' => [
        'title'       => [
          'type'        => 'string',
          'description' => 'Todo title.',
        ],
        'description' => [
          'type'        => 'string',
          'description' => 'Optional todo details.',
        ],
        'due_at'      => [
          'type'        => 'string',
          'description' => 'Optional due date/time (YYYY-MM-DD or YYYY-MM-DD HH:MM).',
        ],
        'priority'    => [
          'type'        => 'string',
          'enum'        => ['low', 'medium', 'high'],
          'description' => 'Todo priority (default: medium).',
        ],
      ],
      'required'   => ['title'],
    ];
  }

  public function execute(array $arguments, ?int $userId = null): mixed
  {
    $user   = getUser($userId);
    $userId = $user?->id;

    if (!$userId) {
      return ['status' => false, 'message' => 'User not authenticated'];
    }

    $title = trim((string) ($arguments['title'] ?? ''));
    if ($title === '') {
      return ['status' => false, 'message' => 'Todo title is required.'];
    }

    $dueStr = !empty($arguments['due_at']) ? Carbon::parse($arguments['due_at'])->format('Y-m-d H:i') : 'No due date';

    return [
      'status'                => true,
      'requires_confirmation' => true,
      'summary'               => "Create todo: \"{$title}\" (Due: {$dueStr})",
      'proposed_data'         => [
        'title'       => $title,
        'description' => $arguments['description'] ?? null,
        'due_at'      => $arguments['due_at'] ?? null,
        'priority'    => $arguments['priority'] ?? 'medium',
      ],
    ];
  }

  public function executeConfirmed(array $arguments, ?int $userId = null): array
  {
    $user   = getUser($userId);
    $userId = $user?->id;

    if (!$userId) {
      return ['status' => false, 'message' => 'User not authenticated'];
    }

    $priorityValue = strtolower((string) ($arguments['priority'] ?? 'medium'));
    $priority      = TodoPriority::tryFrom($priorityValue) ?? TodoPriority::Medium;

    $todo = CalendarTodo::create([
      'user_id'     => $userId,
      'title'       => trim((string) $arguments['title']),
      'description' => $arguments['description'] ?? null,
      'priority'    => $priority,
      'due_at'      => !empty($arguments['due_at']) ? Carbon::parse($arguments['due_at']) : null,
    ]);

    return [
      'status'  => true,
      'message' => "Todo {$todo->code} ({$todo->title}) created.",
      'record'  => [
        'id'    => $todo->id,
        'code'  => $todo->code,
        'title' => $todo->title,
      ],
    ];
  }
}
