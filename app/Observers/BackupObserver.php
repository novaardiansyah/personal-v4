<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\BackupStatus;
use App\Enums\BackupType;
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

    if (empty($backup->server_name)) {
      $backup->server_name = gethostname() ?: config('app.name', 'laravel');
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
    $this->_log('Deleted', $backup);
  }

  public function restored(Backup $backup): void
  {
    $this->_log('Restored', $backup);
  }

  public function forceDeleted(Backup $backup): void
  {
    $this->_log('Force Deleted', $backup);
  }

  private function _sendTelegramNotification(Backup $backup): void
  {
    $statusVal = $backup->status instanceof BackupStatus ? $backup->status->value : strtolower((string) ($backup->status ?? ''));

    if (!in_array($statusVal, ['success', 'failed'], true)) {
      return;
    }

    $scheduleName = '-';
    $fileSize     = $backup->file_size !== null ? sizeFormat((float) $backup->file_size) : '-';
    $type         = $backup->type instanceof BackupType ? $backup->type->value : ($backup->type ?? '-');
    $duration     = $backup->duration !== null ? "{$backup->duration}s" : '-';
    $status       = $statusVal;
    $startedAt    = $backup->started_at ? $backup->started_at->format('Y-m-d H:i:s') : '-';
    $completedAt  = $backup->completed_at ? $backup->completed_at->format('Y-m-d H:i:s') : '-';
    $message      = ($statusVal === 'failed') ? ($backup->message ?: '-') : '-';

    $text = "Schedule Backup Report\n\n"
      . "name: {$scheduleName}\n"
      . "size: {$fileSize}\n"
      . "type: {$type}\n"
      . "duration: {$duration}\n"
      . "status: {$status}\n"
      . "started at: {$startedAt}\n"
      . "completed at: {$completedAt}\n"
      . "message: {$message}";

    SendTelegramNotificationJob::dispatch($text);
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
