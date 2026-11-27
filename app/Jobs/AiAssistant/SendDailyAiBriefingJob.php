<?php

namespace App\Jobs\AiAssistant;

use App\Models\AiAssistantSession;
use App\Services\AiAssistant\AiAssistantService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendDailyAiBriefingJob implements ShouldQueue
{
  use Queueable;

  public function __construct(
    public ?int $userId = null
  ) {}

  public function handle(AiAssistantService $service): void
  {
    $user   = getUser($this->userId);
    $userId = $user?->id;

    if (!$userId) {
      return;
    }

    $context = $service->resolveContext();
    $session = AiAssistantSession::create([
      'user_id'            => $userId,
      'context_id'         => $context?->id,
      'uuid'               => uuid7(),
      'title'              => 'Daily AI Briefing - ' . now()->format('Y-m-d'),
      'last_interacted_at' => now(),
    ]);

    $prompt = 'Provide a concise daily briefing covering: 1) current account balances and this month\'s income/expense summary, 2) upcoming subscription renewals or debt installments, 3) today\'s calendar events and pending todos, and 4) any unhealthy uptime monitors or failed backups.';

    $result = $service->sendMessage($session, $userId, $prompt);

    if (($result['status'] ?? '') === 'completed' && !empty($result['content'])) {
      sendTelegramNotification($result['content'], $user);
    }
  }
}
