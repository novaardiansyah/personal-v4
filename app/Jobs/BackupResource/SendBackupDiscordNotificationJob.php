<?php

declare(strict_types=1);

namespace App\Jobs\BackupResource;

use App\Models\Backup;
use App\Services\DiscordWebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendBackupDiscordNotificationJob implements ShouldQueue
{
  use InteractsWithQueue;
  use Queueable;
  use SerializesModels;

  public function __construct(
    public Backup $backup
  ) {}

  public function handle(DiscordWebhookService $service): void
  {
    $webhookSetting = getSetting('discord_webhook_report', null, Backup::class);
    $webhookUrl     = $service->resolveWebhookUrl($webhookSetting);

    if (empty($webhookUrl)) {
      return;
    }

    $payload = $service->buildBackupReportPayload($this->backup);
    $service->send($webhookUrl, $payload);
  }
}
