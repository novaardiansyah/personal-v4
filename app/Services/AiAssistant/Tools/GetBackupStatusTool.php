<?php

namespace App\Services\AiAssistant\Tools;

use App\Models\Backup;
use App\Models\BackupSchedule;

class GetBackupStatusTool implements AiAssistantToolInterface
{
  public function getName(): string
  {
    return 'get_backup_status';
  }

  public function getDescription(): string
  {
    return 'Inspect recent backup runs, statuses, file sizes, and active backup schedules.';
  }

  public function getParametersSchema(): array
  {
    return [
      'type'       => 'object',
      'properties' => [
        'limit' => [
          'type'        => 'integer',
          'description' => 'Max recent backup runs to return (default 10, max 25).',
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

    $limit = min((int) ($arguments['limit'] ?? 10), 25);

    $recentBackups = Backup::with(['schedule', 'storage'])
      ->latest('id')
      ->take($limit)
      ->get()
      ->map(function ($b) {
        return [
          'uid'          => $b->uid,
          'schedule'     => $b->schedule?->name ?? '-',
          'storage'      => $b->storage?->name ?? '-',
          'type'         => $b->type?->value ?? (string) $b->type,
          'status'       => $b->status?->value ?? (string) $b->status,
          'file_name'    => $b->file_name,
          'file_size'    => $b->file_size,
          'duration_sec' => $b->duration,
          'completed_at' => $b->completed_at?->format('Y-m-d H:i:s'),
          'message'      => $b->message,
        ];
      })
      ->toArray();

    $schedules = BackupSchedule::where('is_enabled', true)
      ->orderBy('next_backup_at')
      ->get()
      ->map(function ($s) {
        return [
          'name'           => $s->name,
          'type'           => $s->type?->value ?? (string) $s->type,
          'count_backup'   => $s->count_backup,
          'last_backup_at' => $s->last_backup_at?->format('Y-m-d H:i:s'),
          'next_backup_at' => $s->next_backup_at?->format('Y-m-d H:i:s'),
        ];
      })
      ->toArray();

    return [
      'recent_backups'   => $recentBackups,
      'active_schedules' => $schedules,
    ];
  }
}
