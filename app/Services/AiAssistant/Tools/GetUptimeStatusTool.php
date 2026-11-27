<?php

namespace App\Services\AiAssistant\Tools;

use App\Models\UptimeMonitor;

class GetUptimeStatusTool implements AiAssistantToolInterface
{
  public function getName(): string
  {
    return 'get_uptime_status';
  }

  public function getDescription(): string
  {
    return 'Check current health, uptime percentage, and latest check timestamps for monitored endpoints.';
  }

  public function getParametersSchema(): array
  {
    return [
      'type'       => 'object',
      'properties' => [
        'only_active' => [
          'type'        => 'boolean',
          'description' => 'Filter to active monitors only (default true).',
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

    $onlyActive = $arguments['only_active'] ?? true;
    $query      = UptimeMonitor::query();

    if ($onlyActive) {
      $query->where('is_active', true);
    }

    $records = $query->orderBy('name')->get();

    $monitors = $records->map(function ($m) {
      $total      = (int) $m->total_checks;
      $healthy    = (int) $m->healthy_checks;
      $uptimeRate = $total > 0 ? round(($healthy / $total) * 100, 2) : 0;

      return [
        'code'              => $m->code,
        'name'              => $m->name,
        'url'               => $m->url,
        'status'            => $m->status?->value ?? (string) $m->status,
        'is_active'         => (bool) $m->is_active,
        'uptime_percent'    => $uptimeRate,
        'total_checks'      => $total,
        'unhealthy_checks'  => (int) $m->unhealthy_checks,
        'last_checked_at'   => $m->last_checked_at?->format('Y-m-d H:i:s'),
        'last_unhealthy_at' => $m->last_unhealthy_at?->format('Y-m-d H:i:s'),
      ];
    })->toArray();

    return [
      'count'    => count($monitors),
      'monitors' => $monitors,
    ];
  }
}
