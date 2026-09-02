<?php

namespace App\Jobs\DiscordResource;

use App\Enums\DiscordMessageStatus;
use App\Models\DiscordMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class SendPendingDiscordMessagesJob implements ShouldQueue
{
  use Queueable;

  public function handle(): void
  {
    DiscordMessage::query()
      ->where('status', DiscordMessageStatus::Pending)
      ->whereNotNull('webhook_id')
      ->with('webhook')
      ->chunkById(10, function ($messages) {
        foreach ($messages as $message) {
          $this->processMessage($message);
        }
      });
  }

  private function processMessage(DiscordMessage $message): void
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

    try {
      $response = Http::timeout(30)->post($webhook->url, $payload);

      $responseData = $response->json() ?? [
        'status' => $response->status(),
        'body'   => $response->body(),
      ];

      $status = $response->successful()
        ? DiscordMessageStatus::Success
        : DiscordMessageStatus::Failed;

      $message->update([
        'status'   => $status,
        'response' => is_array($responseData) ? $responseData : ['data' => $responseData],
      ]);
    } catch (\Throwable $e) {
      $message->update([
        'status'   => DiscordMessageStatus::Failed,
        'response' => ['error' => $e->getMessage()],
      ]);
    }
  }
}
