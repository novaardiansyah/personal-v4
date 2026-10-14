<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\BackupStatus;
use App\Enums\DiscordMessageStatus;
use App\Jobs\SendTelegramNotificationJob;
use App\Models\Backup;
use App\Models\DiscordMessage;
use App\Services\DiscordWebhookService;

class BackupObserver
{
  public function creating(Backup $backup): void
  {
    if (empty($backup->uid)) {
      $backup->uid = uuid7();
    }

    if (empty($backup->started_at)) {
      $backup->started_at = now();
    }
  }

  public function updating(Backup $backup): void
  {
    if (empty($backup->uid)) {
      $backup->uid = uuid7();
    }
  }

  public function created(Backup $backup): void
  {
    $this->_log('Created', $backup);
    $this->_sendTelegramNotification($backup);
    $this->_sendDiscordNotification($backup);
  }

  public function updated(Backup $backup): void
  {
    $this->_log('Updated', $backup);

    if ($backup->wasChanged('status')) {
      $this->_sendTelegramNotification($backup);
      $this->_sendDiscordNotification($backup);
    }
  }

  public function deleted(Backup $backup): void
  {
    $backup->deleteFiles();
    $this->_log('Deleted', $backup);
  }

  public function restored(Backup $backup): void
  {
    $this->_log('Restored', $backup);
  }

  public function forceDeleted(Backup $backup): void
  {
    $backup->deleteFiles();
    $this->_log('Force Deleted', $backup);
  }

  private function _sendTelegramNotification(Backup $backup): void
  {
    $statusVal = $backup->status instanceof BackupStatus ? $backup->status->value : strtolower((string) ($backup->status ?? ''));

    if (!in_array($statusVal, ['success', 'failed'], true)) {
      return;
    }

    $scheduleName = $backup->schedule?->name ?? '-';
    $serverName   = $backup->server?->name ?? '-';
    $cloudName    = $backup->storage?->name ?? '-';
    $fileName     = $backup->file_name ?? '-';
    $fileSize     = $backup->file_size !== null ? sizeFormat((float) $backup->file_size) : '-';
    $duration     = $backup->duration !== null ? (secondsToHumanReadable((int) $backup->duration, true) ?: '0s') : '-';
    $message      = $backup->message ?: '-';
    $startedAt    = $backup->started_at ? $backup->started_at->format('Y-m-d H:i:s') : '-';
    $completedAt  = $backup->completed_at ? $backup->completed_at->format('Y-m-d H:i:s') : '-';
    $previewUrl   = url("admin/backups/{$backup->id}");

    $text = "Schedule Backup Report\n\n"
      . "Name: {$scheduleName}\n"
      . "Server: {$serverName}\n"
      . "Cloud: {$cloudName}\n"
      . "File Name: {$fileName}\n"
      . "Size: {$fileSize}\n"
      . "Duration: {$duration}\n"
      . "Message: {$message}\n"
      . "Started At: {$startedAt}\n"
      . "Completed At: {$completedAt}\n"
      . "Preview: {$previewUrl}";

    SendTelegramNotificationJob::dispatch($text, ['parse_mode' => '']);
  }

  private function _sendDiscordNotification(Backup $backup): void
  {
    $statusVal = $backup->status instanceof BackupStatus ? $backup->status->value : strtolower((string) ($backup->status ?? ''));

    if (!in_array($statusVal, ['success', 'failed'], true)) {
      return;
    }

    $webhookSetting = getSetting('discord_webhook_report', null, Backup::class);
    if (empty($webhookSetting)) {
      return;
    }

    $service = app(DiscordWebhookService::class);
    $webhook = $service->resolveWebhook($webhookSetting);

    if (!$webhook) {
      return;
    }

    $payload = $service->buildBackupReportPayload($backup);

    DiscordMessage::create([
      'webhook_id' => $webhook->id,
      'content'    => $payload,
      'status'     => DiscordMessageStatus::Pending,
    ]);
  }

  private function _log(string $event, Backup $backup): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'Backup',
      'subject_type' => Backup::class,
      'subject_id'   => $backup->id,
    ], $backup);
  }
}
