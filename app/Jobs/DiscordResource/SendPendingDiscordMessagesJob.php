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

  public function handle(DiscordWebhookService $service): void
  {
    DiscordMessage::query()
      ->where('status', DiscordMessageStatus::Pending)
      ->whereNotNull('webhook_id')
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
      $message->update([
        'status'   => DiscordMessageStatus::Failed,
        'response' => ['error' => 'Webhook URL not found or webhook missing'],
      ]);

      return;
    }

    $payload = $message->content;
    if (!is_array($payload)) {
      $payload = ['content' => (string) $payload];
    }

    $result = $service->send($webhook->url, $payload);

    $message->update([
      'status'   => $result['success'] ? DiscordMessageStatus::Success : DiscordMessageStatus::Failed,
      'response' => $result['response'],
    ]);
  }
}
