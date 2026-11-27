<?php

namespace App\Services\AiAssistant\Tools;

use App\Models\ActivityLog;

class GetActivityLogsTool implements AiAssistantToolInterface
{
  public function getName(): string
  {
    return 'get_activity_logs';
  }

  public function getDescription(): string
  {
    return 'Retrieve recent system and user activity log entries.';
  }

  public function getParametersSchema(): array
  {
    return [
      'type'       => 'object',
      'properties' => [
        'event' => [
          'type'        => 'string',
          'description' => 'Optional event filter (e.g. Created, Updated, Deleted, Login).',
        ],
        'limit' => [
          'type'        => 'integer',
          'description' => 'Max log entries to return (default 10, max 25).',
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

    $query = ActivityLog::latest('id');

    if (!empty($arguments['event'])) {
      $query->where('event', $arguments['event']);
    }

    $limit = min((int) ($arguments['limit'] ?? 10), 25);

    $logs = $query->take($limit)->get()->map(function ($log) {
      return [
        'log_name'    => $log->log_name,
        'event'       => $log->event,
        'description' => $log->description,
        'ip_address'  => $log->ip_address,
        'created_at'  => $log->created_at?->format('Y-m-d H:i:s'),
      ];
    })->toArray();

    return [
      'count' => count($logs),
      'logs'  => $logs,
    ];
  }
}
