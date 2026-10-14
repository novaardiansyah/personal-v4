<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BackupStatus;
use App\Models\Backup;
use App\Models\DiscordWebhook;
use App\Models\User;
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
    $statusVal    = $backup->status instanceof BackupStatus ? $backup->status->value : strtolower((string) ($backup->status ?? ''));
    $scheduleName = $backup->schedule?->name ?? '-';
    $serverName   = $backup->server?->name ?: (gethostname() ?: config('app.name', 'laravel'));
    $cloudName    = $backup->storage?->name ?? '-';
    $fileName     = $backup->file_name ?? '-';
    $fileSize     = $backup->file_size !== null ? sizeFormat((float) $backup->file_size) : '-';
    $duration     = $backup->duration !== null ? (secondsToHumanReadable((int) $backup->duration) ?: '0s') : '-';
    $startedAt    = $backup->started_at ? $backup->started_at->format('Y-m-d H:i:s') : '-';
    $completedAt  = $backup->completed_at ? $backup->completed_at->format('Y-m-d H:i:s') : '-';
    $previewUrl   = url("admin/backups/{$backup->id}");

    $fields = [
      [
        'name'   => 'Name',
        'value'  => (string) $scheduleName,
        'inline' => true,
      ],
      [
        'name'   => 'Server',
        'value'  => (string) $serverName,
        'inline' => true,
      ],
      [
        'name'   => 'Cloud',
        'value'  => (string) $cloudName,
        'inline' => true,
      ],
      [
        'name'   => 'File Name',
        'value'  => (string) $fileName,
        'inline' => true,
      ],
      [
        'name'   => 'Size',
        'value'  => (string) $fileSize,
        'inline' => true,
      ],
      [
        'name'   => 'Duration',
        'value'  => (string) $duration,
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
      [
        'name'   => 'Message',
        'value'  => !empty($backup->message) ? "```\n" . Str::limit($backup->message, 1000) . "\n```" : '-',
        'inline' => false,
      ],
      [
        'name'   => 'Preview',
        'value'  => (string) $previewUrl,
        'inline' => false,
      ],
    ];

    return [
      'embeds' => [
        [
          'title'     => '📦 Schedule Backup Report',
          'url'       => $previewUrl,
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

  public function buildLoginReportPayload(User $user, array $context): array
  {
    $ipAddress   = $context['device_info']['ip_address'] ?? '-';
    $address     = $context['address'] ?? '-';
    $geolocation = $context['device_info']['geolocation'] ?? '-';
    $timezone    = $context['device_info']['timezone'] ?? '-';
    $userAgent   = $context['device_info']['user_agent'] ?? '-';
    $loginDate   = $context['now_formatted'] ?? now()->format('d M Y, H:i');
    $referer     = $context['device_info']['referer'] ?? '-';
    $guard       = strtoupper($context['guard'] ?? 'web');

    $fields = [
      [
        'name'   => 'User',
        'value'  => $user->name . ' (' . $user->email . ')',
        'inline' => false,
      ],
      [
        'name'   => 'IP Address',
        'value'  => (string) $ipAddress,
        'inline' => true,
      ],
      [
        'name'   => 'Location',
        'value'  => (string) $address,
        'inline' => true,
      ],
      [
        'name'   => 'Timezone',
        'value'  => (string) $timezone,
        'inline' => true,
      ],
      [
        'name'   => 'Login Time',
        'value'  => $loginDate . ' WIB',
        'inline' => true,
      ],
      [
        'name'   => 'Guard',
        'value'  => (string) $guard,
        'inline' => true,
      ],
      [
        'name'   => 'Geolocation',
        'value'  => (string) $geolocation,
        'inline' => true,
      ],
      [
        'name'   => 'Referer',
        'value'  => (string) $referer,
        'inline' => false,
      ],
      [
        'name'   => 'Device',
        'value'  => (string) $userAgent,
        'inline' => false,
      ],
    ];

    return [
      'embeds' => [
        [
          'title'     => '🔐 User Login Notification',
          'color'     => 0x3B82F6,
          'fields'    => $fields,
          'footer'    => [
            'text' => config('app.name', 'Personal V4') . ' • Login Notification',
          ],
          'timestamp' => now()->toISOString(),
        ],
      ],
    ];
  }

  public function buildBackupCleanupReportPayload(int $totalDeletedFiles, int $totalDeletedSize): array
  {
    $formattedSize = sizeFormat((float) $totalDeletedSize);
    $serverName    = gethostname() ?: config('app.name', 'laravel');

    $fields = [
      [
        'name'   => 'Server',
        'value'  => (string) $serverName,
        'inline' => true,
      ],
      [
        'name'   => 'Status',
        'value'  => $totalDeletedFiles > 0 ? '🧹 Cleaned' : '✅ No Excess Files',
        'inline' => true,
      ],
      [
        'name'   => 'Files Deleted',
        'value'  => (string) $totalDeletedFiles,
        'inline' => true,
      ],
      [
        'name'   => 'Size Deleted',
        'value'  => (string) $formattedSize,
        'inline' => true,
      ],
      [
        'name'   => 'Executed At',
        'value'  => now()->format('Y-m-d H:i:s'),
        'inline' => true,
      ],
    ];

    return [
      'embeds' => [
        [
          'title'     => '🧹 Schedule Backup Cleanup Report',
          'color'     => $totalDeletedFiles > 0 ? 0x3B82F6 : 0x22C55E,
          'fields'    => $fields,
          'footer'    => [
            'text' => config('app.name', 'Personal V4') . ' • Backup Notification',
          ],
          'timestamp' => now()->toISOString(),
        ],
      ],
    ];
  }
}
