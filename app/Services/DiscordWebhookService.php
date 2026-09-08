<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BackupStatus;
use App\Enums\BackupType;
use App\Models\Backup;
use App\Models\DiscordWebhook;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DiscordWebhookService
{
  public function send(string $webhookUrl, array $payload): array
  {
    try {
      $response = Http::timeout(30)->post($webhookUrl, $payload);

      $responseData = $response->json() ?? [
        'status' => $response->status(),
        'body'   => $response->body(),
      ];

      return [
        'success'  => $response->successful(),
        'response' => is_array($responseData) ? $responseData : ['data' => $responseData],
      ];
    } catch (\Throwable $e) {
      Log::error('Failed sending Discord webhook: ' . $e->getMessage(), [
        'webhook_url' => $webhookUrl,
        'error'       => $e->getMessage(),
      ]);

      return [
        'success'  => false,
        'response' => ['error' => $e->getMessage()],
      ];
    }
  }

  public function resolveWebhook(?string $webhookSetting): ?DiscordWebhook
  {
    if (empty($webhookSetting)) {
      return null;
    }

    $webhookQuery = DiscordWebhook::where('uid', $webhookSetting)
      ->orWhere('uid', strtolower($webhookSetting));

    if (is_numeric($webhookSetting)) {
      $webhookQuery->orWhere('id', (int) $webhookSetting);
    }

    return $webhookQuery->first();
  }

  public function resolveWebhookUrl(?string $webhookSetting): ?string
  {
    if (empty($webhookSetting)) {
      return null;
    }

    if (str_starts_with($webhookSetting, 'http://') || str_starts_with($webhookSetting, 'https://')) {
      return $webhookSetting;
    }

    return $this->resolveWebhook($webhookSetting)?->url;
  }

  public function buildBackupReportPayload(Backup $backup): array
  {
    $backup->loadMissing('backupJob.backupSchedule');

    $statusVal    = $backup->status instanceof BackupStatus ? $backup->status->value : strtolower((string) ($backup->status ?? ''));
    $scheduleName = $backup->backupJob?->backupSchedule?->name ?? '-';
    $fileSize     = $backup->file_size !== null ? sizeFormat((float) $backup->file_size) : '-';
    $type         = $backup->type instanceof BackupType ? $backup->type->value : ($backup->type ?? '-');
    $duration     = $backup->duration !== null ? "{$backup->duration}s" : '-';
    $startedAt    = $backup->started_at ? $backup->started_at->format('Y-m-d H:i:s') : '-';
    $completedAt  = $backup->completed_at ? $backup->completed_at->format('Y-m-d H:i:s') : '-';
    $serverName   = $backup->server_name ?: (gethostname() ?: config('app.name', 'laravel'));

    $fields = [
      [
        'name'   => 'Schedule',
        'value'  => (string) $scheduleName,
        'inline' => true,
      ],
      [
        'name'   => 'Status',
        'value'  => $statusVal === 'success' ? '✅ Success' : '❌ Failed',
        'inline' => true,
      ],
      [
        'name'   => 'Type',
        'value'  => ucfirst((string) $type),
        'inline' => true,
      ],
      [
        'name'   => 'File Size',
        'value'  => (string) $fileSize,
        'inline' => true,
      ],
      [
        'name'   => 'Duration',
        'value'  => (string) $duration,
        'inline' => true,
      ],
      [
        'name'   => 'Server',
        'value'  => (string) $serverName,
        'inline' => true,
      ],
      [
        'name'   => 'Started At',
        'value'  => (string) $startedAt,
        'inline' => true,
      ],
      [
        'name'   => 'Completed At',
        'value'  => (string) $completedAt,
        'inline' => true,
      ],
    ];

    if ($statusVal === 'failed' && !empty($backup->message)) {
      $fields[] = [
        'name'   => 'Message',
        'value'  => "```\n" . Str::limit($backup->message, 1000) . "\n```",
        'inline' => false,
      ];
    }

    return [
      'embeds' => [
        [
          'title'     => '📦 Schedule Backup Report',
          'color'     => $statusVal === 'success' ? 0x22C55E : 0xEF4444,
          'fields'    => $fields,
          'footer'    => [
            'text' => config('app.name', 'Personal V4') . ' • Backup Notification',
          ],
          'timestamp' => $backup->completed_at?->toISOString() ?? now()->toISOString(),
        ],
      ],
    ];
  }
}
