<?php

namespace App\Jobs\DiscordResource;

use App\Enums\DiscordMessageStatus;
use App\Models\DiscordMessage;
use App\Services\DiscordWebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendPendingDiscordMessagesJob implements ShouldQueue
{
  use Queueable;

  public function __construct(
    public int $delaySeconds = 5,
    public int $maxRetries = 3
  ) {}

  public function handle(DiscordWebhookService $service): void
  {
    DiscordMessage::query()
      ->whereNotNull('webhook_id')
      ->where(function ($query) {
        $query->where('status', DiscordMessageStatus::Pending)
          ->orWhere(function ($query) {
            $query->where('status', DiscordMessageStatus::Failed)
              ->where('count_retry', '<', $this->maxRetries);
          });
      })
      ->with('webhook')
      ->chunkById(10, function ($messages) use ($service) {
        foreach ($messages as $message) {
          $this->processMessage($message, $service);
        }
      });
  }

  private function processMessage(DiscordMessage $message, DiscordWebhookService $service): void
  {
    $webhook = $message->webhook;

    if (!$webhook || empty($webhook->url)) {
      $updates = [
        'status'   => DiscordMessageStatus::Failed,
        'response' => ['error' => 'Webhook URL not found or webhook missing'],
      ];

      if ($message->status === DiscordMessageStatus::Failed) {
        $updates['count_retry'] = $message->count_retry + 1;
      }

      $message->update($updates);

      return;
    }

    $payload = $message->content;
    if (!is_array($payload)) {
      $payload = ['content' => (string) $payload];
    }

    $result = $service->send($webhook->url, $payload);

    $updates = [
      'status'   => $result['success'] ? DiscordMessageStatus::Success : DiscordMessageStatus::Failed,
      'response' => $result['response'],
    ];

    if ($message->status === DiscordMessageStatus::Failed) {
      $updates['count_retry'] = $message->count_retry + 1;
    }

    $message->update($updates);

    if ($this->delaySeconds > 0) {
      sleep($this->delaySeconds);
    }
  }
}
