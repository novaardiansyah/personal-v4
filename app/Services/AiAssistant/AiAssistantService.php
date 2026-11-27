<?php

namespace App\Services\AiAssistant;

use App\Models\AiAssistantContext;
use App\Models\AiAssistantMessage;
use App\Models\AiAssistantSession;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mpdf\Mpdf;

class AiAssistantService
{
  public function __construct(
    protected AiLlmClient $client,
    protected AiToolRegistry $toolRegistry
  ) {}

  public function resolveContext(?int $contextId = null): ?AiAssistantContext
  {
    if ($contextId) {
      $selected = AiAssistantContext::active()->find($contextId);
      if ($selected) {
        return $selected;
      }
    }

    return AiAssistantContext::active()->where('name', 'Payments Assistant')->first()
      ?? AiAssistantContext::active()->ordered()->first();
  }

  public function buildSystemPrompt(?AiAssistantContext $context, ?string $sessionSummary = null): string
  {
    $baseSystemPrompt = $context?->system_prompt ?? 'You are a helpful AI assistant.';
    $currentDateTime  = now()->translatedFormat('l, d F Y H:i:s (e)');

    $responseGuidelines = implode("\n", [
      'Response Guidelines:',
      '- Always respond in the exact same language as the user\'s latest message (e.g. respond in Indonesian if the user writes in Indonesian, or English if in English).',
      '- Never include internal reasoning, tool-call planning, or system context instructions in your response.',
      '- Provide a direct, clear, and concise answer based only on verified tool results.',
      '- When a write tool returns requires_confirmation=true, clearly summarize the proposed action to the user and ask them to click the Confirm button in the interface.',
    ]);

    $parts = [
      "Current Date & Time: {$currentDateTime}",
      $baseSystemPrompt,
    ];

    if (!empty($sessionSummary)) {
      $parts[] = "Conversation Summary:\n{$sessionSummary}";
    }

    $parts[] = $responseGuidelines;

    return implode("\n\n", $parts);
  }

  public function buildConversationMessages(AiAssistantSession $session, string $systemPrompt, int $limit = 14): array
  {
    $formattedMessages = [
      [
        'role'    => 'system',
        'content' => $systemPrompt,
      ],
    ];

    $history = AiAssistantMessage::forSession($session->id)
      ->whereIn('role', ['user', 'assistant'])
      ->where('status', 'completed')
      ->whereNotNull('content')
      ->latest('id')
      ->take($limit)
      ->get()
      ->reverse()
      ->values();

    foreach ($history as $msg) {
      $contentText = trim((string) $msg->content);
      if ($contentText === '') {
        continue;
      }

      $attachments = $msg->metadata['attachments'] ?? [];
      if ($msg->role === 'user' && !empty($attachments) && is_array($attachments)) {
        $contentBlocks = [
          [
            'type' => 'text',
            'text' => $contentText,
          ],
        ];

        foreach ($attachments as $att) {
          $path = $att['path'] ?? null;
          $mime = $att['mime'] ?? 'image/png';
          if ($path && str_starts_with($mime, 'image/') && Storage::disk('public')->exists($path)) {
            $raw = Storage::disk('public')->get($path);
            if ($raw !== null) {
              $contentBlocks[] = [
                'type'      => 'image_url',
                'image_url' => [
                  'url' => 'data:' . $mime . ';base64,' . base64_encode($raw),
                ],
              ];
            }
          }
        }

        $formattedMessages[] = [
          'role'    => 'user',
          'content' => $contentBlocks,
        ];
      } else {
        $formattedMessages[] = [
          'role'    => $msg->role,
          'content' => $contentText,
        ];
      }
    }

    return $formattedMessages;
  }

  public function checkRateLimit(int $userId): ?string
  {
    $rpmLimit = (int) getSetting('ai_assistant_rate_limit_per_minute', 20);
    if ($rpmLimit > 0) {
      $recentCount = AiAssistantMessage::forUser($userId)
        ->where('role', 'user')
        ->where('created_at', '>=', now()->subMinute())
        ->count();

      if ($recentCount >= $rpmLimit) {
        return "Rate limit exceeded ({$rpmLimit} requests per minute). Please wait a moment before sending another message.";
      }
    }

    $dailyTokenLimit = (int) getSetting('ai_assistant_daily_token_limit', 0);
    if ($dailyTokenLimit > 0) {
      $todayTokens = (int) AiAssistantMessage::forUser($userId)
        ->where('created_at', '>=', now()->startOfDay())
        ->sum('token_total');

      if ($todayTokens >= $dailyTokenLimit) {
        return "Daily token quota ({$dailyTokenLimit} tokens) has been reached. Please try again tomorrow.";
      }
    }

    return null;
  }

  public function sendMessage(
    AiAssistantSession $session,
    int $userId,
    string $prompt,
    array $options = [],
    ?callable $onStream = null,
    ?callable $onStatus = null
  ): array {
    $startTime    = microtime(true);
    $trimmed      = trim($prompt);
    $skipUserSave = (bool) ($options['skip_user_save'] ?? false);
    $attachments  = $options['attachments'] ?? [];
    $contextId    = $options['context_id'] ?? $session->context_id;
    $context      = $this->resolveContext($contextId ? (int) $contextId : null);

    $rateError = $this->checkRateLimit($userId);
    if ($rateError !== null) {
      return [
        'status'            => 'failed',
        'content'           => "### ⚠️ Rate Limit Reached\n\n{$rateError}",
        'reasoning_content' => null,
        'model_used'        => $session->model_override ?: config('services.ai_assistant.model'),
        'token_prompt'      => 0,
        'token_completion'  => 0,
        'token_total'       => 0,
        'latency_ms'        => 0,
        'cost'              => 0.0,
        'metadata'          => null,
        'error_message'     => $rateError,
      ];
    }

    if (!$skipUserSave) {
      AiAssistantMessage::create([
        'uuid'       => uuid7(),
        'session_id' => $session->id,
        'user_id'    => $userId,
        'role'       => 'user',
        'content'    => $trimmed,
        'model_used' => null,
        'status'     => 'completed',
        'metadata'   => !empty($attachments) ? ['attachments' => $attachments] : null,
      ]);

      if (str_starts_with($session->title, 'Conversation #') || $session->title === 'New Conversation') {
        $session->title = Str::limit($trimmed, 40);
      }
      $session->last_interacted_at = now();
      $session->save();
    }

    $model = $options['model']
      ?: ($session->model_override
        ?: ($context?->default_model ?: config('services.ai_assistant.model')));

    $maxTokens    = (int) ($context?->max_tokens ?? config('services.ai_assistant.max_tokens', 3072));
    $temperature  = (float) ($context?->temperature ?? config('services.ai_assistant.temperature', 0.3));
    $systemPrompt = $this->buildSystemPrompt($context, $session->summary);
    $messages     = $this->buildConversationMessages($session, $systemPrompt);
    $toolsSchema  = $this->toolRegistry->getToolsSchema($context?->allowed_tools);

    $payload = [
      'model'       => $model,
      'max_tokens'  => $maxTokens,
      'temperature' => $temperature,
      'messages'    => $messages,
    ];

    if (!empty($toolsSchema)) {
      $payload['tools'] = $toolsSchema;
    }

    $totalPromptTokens     = 0;
    $totalCompletionTokens = 0;
    $totalTokens           = 0;
    $totalCost             = 0.0;
    $executedToolsInfo     = [];
    $pendingActions        = [];
    $finalData             = null;
    $lastError             = null;
    $maxToolIterations     = 5;

    for ($iteration = 0; $iteration < $maxToolIterations; $iteration++) {
      $isLastAllowed = ($iteration === $maxToolIterations - 1);
      if ($isLastAllowed) {
        unset($payload['tools']);
      }

      if ($onStream !== null && $isLastAllowed) {
        $result = $this->client->streamChat($payload, $onStream);
      } else {
        $result = $this->client->chat($payload);
      }

      if (!$result['ok'] || !is_array($result['data'])) {
        $lastError = $result['error'] ?? 'LLM request failed.';
        $finalData = null;
        break;
      }

      $data      = $result['data'];
      $finalData = $data;
      $usage     = $data['usage'] ?? [];

      $totalPromptTokens     += (int) ($usage['prompt_tokens'] ?? 0);
      $totalCompletionTokens += (int) ($usage['completion_tokens'] ?? 0);
      $totalTokens           += (int) ($usage['total_tokens'] ?? 0);
      $totalCost             += (float) ($data['cost'] ?? 0);

      $messageObj = $data['choices'][0]['message'] ?? [];
      $toolCalls  = $messageObj['tool_calls'] ?? null;

      if (empty($toolCalls) || !is_array($toolCalls)) {
        if ($onStream !== null && !$isLastAllowed) {
          $streamedText = (string) ($messageObj['content'] ?? '');
          if ($streamedText !== '') {
            $onStream($streamedText, (string) ($messageObj['reasoning_content'] ?? $messageObj['reasoning'] ?? ''));
          }
        }
        break;
      }

      $messages[] = $messageObj;

      AiAssistantMessage::create([
        'uuid'       => uuid7(),
        'session_id' => $session->id,
        'user_id'    => $userId,
        'role'       => 'assistant',
        'content'    => $messageObj['content'] ?? null,
        'model_used' => $data['model'] ?? $model,
        'status'     => 'completed',
        'metadata'   => [
          'step'       => 'tool_call_request',
          'tool_calls' => $toolCalls,
        ],
      ]);

      foreach ($toolCalls as $tc) {
        $toolCallId   = $tc['id'] ?? null;
        $functionName = $tc['function']['name'] ?? '';
        $rawArgs      = $tc['function']['arguments'] ?? '{}';

        $arguments = is_string($rawArgs) ? json_decode($rawArgs, true) : (array) $rawArgs;
        if (!is_array($arguments)) {
          $arguments = [];
        }

        if ($onStatus !== null) {
          $onStatus("Executing {$functionName}...");
        }

        $toolResult          = $this->toolRegistry->executeTool($functionName, $arguments, $userId);
        $executedToolsInfo[] = [
          'name'      => $functionName,
          'arguments' => $arguments,
          'result'    => $toolResult,
        ];

        if (is_array($toolResult) && !empty($toolResult['requires_confirmation'])) {
          $pendingActions[] = [
            'action_id' => uuid7(),
            'tool'      => $functionName,
            'arguments' => $arguments,
            'summary'   => $toolResult['summary'] ?? $functionName,
            'status'    => 'pending',
          ];
        }

        AiAssistantMessage::create([
          'uuid'       => uuid7(),
          'session_id' => $session->id,
          'user_id'    => $userId,
          'role'       => 'tool',
          'content'    => json_encode($toolResult),
          'model_used' => $data['model'] ?? $model,
          'status'     => 'completed',
          'metadata'   => [
            'step'          => 'tool_call_result',
            'tool_call_id'  => $toolCallId,
            'function_name' => $functionName,
            'arguments'     => $arguments,
          ],
        ]);

        $messages[] = [
          'role'         => 'tool',
          'tool_call_id' => $toolCallId,
          'content'      => json_encode($toolResult),
        ];
      }

      $payload['messages'] = $messages;
    }

    $latencyMs = (int) round((microtime(true) - $startTime) * 1000);

    if (is_array($finalData)) {
      $content          = trim((string) ($finalData['choices'][0]['message']['content'] ?? ''));
      $reasoningContent = $finalData['choices'][0]['message']['reasoning']
        ?? $finalData['choices'][0]['message']['reasoning_content']
        ?? null;

      if ($content !== '') {
        $returnedModel = $finalData['model'] ?? $model;
        $usage         = $finalData['usage'] ?? [];

        $metadata = array_filter([
          'context_name'              => $context?->name ?? null,
          'executed_tools'            => !empty($executedToolsInfo) ? $executedToolsInfo : null,
          'pending_actions'           => !empty($pendingActions) ? $pendingActions : null,
          'response_id'               => $finalData['id'] ?? null,
          'finish_reason'             => $finalData['choices'][0]['finish_reason'] ?? null,
          'prompt_tokens_details'     => $usage['prompt_tokens_details'] ?? null,
          'completion_tokens_details' => $usage['completion_tokens_details'] ?? null,
        ], fn ($val) => $val !== null);

        AiAssistantMessage::where('session_id', $session->id)
          ->where('role', 'user')
          ->whereNull('model_used')
          ->latest('id')
          ->first()
          ?->update(['model_used' => $returnedModel]);

        $savedMessage = AiAssistantMessage::create([
          'uuid'              => uuid7(),
          'session_id'        => $session->id,
          'user_id'           => $userId,
          'role'              => 'assistant',
          'content'           => $content,
          'reasoning_content' => $reasoningContent,
          'token_prompt'      => $totalPromptTokens,
          'token_completion'  => $totalCompletionTokens,
          'token_total'       => $totalTokens,
          'latency_ms'        => $latencyMs,
          'cost'              => $totalCost,
          'model_used'        => $returnedModel,
          'status'            => 'completed',
          'metadata'          => !empty($metadata) ? $metadata : null,
        ]);

        if ($totalTokens > 0) {
          $session->increment('total_tokens_used', $totalTokens);
        }
        if ($totalCost > 0) {
          $session->increment('total_cost', $totalCost);
        }
        $session->update(['last_interacted_at' => now()]);

        return [
          'status'            => 'completed',
          'message_uuid'      => $savedMessage->uuid,
          'content'           => $content,
          'reasoning_content' => $reasoningContent,
          'model_used'        => $returnedModel,
          'token_prompt'      => $totalPromptTokens,
          'token_completion'  => $totalCompletionTokens,
          'token_total'       => $totalTokens,
          'latency_ms'        => $latencyMs,
          'cost'              => $totalCost,
          'metadata'          => !empty($metadata) ? $metadata : null,
          'error_message'     => null,
        ];
      }

      $lastError = $lastError ?? 'Model returned empty content.';
      Log::error('AI Assistant empty content response', [
        'model'         => $model,
        'finish_reason' => $finalData['choices'][0]['finish_reason'] ?? null,
        'has_reasoning' => !empty($reasoningContent),
      ]);
    }

    $fallbackContent = $this->generateLocalFallbackResponse();

    $failedMessage = AiAssistantMessage::create([
      'uuid'             => uuid7(),
      'session_id'       => $session->id,
      'user_id'          => $userId,
      'role'             => 'assistant',
      'content'          => $fallbackContent,
      'token_prompt'     => $totalPromptTokens,
      'token_completion' => $totalCompletionTokens,
      'token_total'      => $totalTokens,
      'latency_ms'       => $latencyMs,
      'cost'             => $totalCost,
      'model_used'       => $model,
      'status'           => 'failed',
      'error_message'    => $lastError,
      'metadata'         => !empty($executedToolsInfo) ? ['executed_tools' => $executedToolsInfo] : null,
    ]);

    return [
      'status'            => 'failed',
      'message_uuid'      => $failedMessage->uuid,
      'content'           => $fallbackContent,
      'reasoning_content' => null,
      'model_used'        => $model,
      'token_prompt'      => $totalPromptTokens,
      'token_completion'  => $totalCompletionTokens,
      'token_total'       => $totalTokens,
      'latency_ms'        => $latencyMs,
      'cost'              => $totalCost,
      'metadata'          => $failedMessage->metadata,
      'error_message'     => $lastError,
    ];
  }

  public function confirmPendingAction(
    AiAssistantSession $session,
    int $userId,
    string $messageUuid,
    string $actionId,
    bool $approved
  ): array {
    $message = AiAssistantMessage::forSession($session->id)
      ->forUser($userId)
      ->where('uuid', $messageUuid)
      ->first();

    if (!$message) {
      return ['status' => false, 'message' => 'Message not found.'];
    }

    $metadata       = $message->metadata ?? [];
    $pendingActions = $metadata['pending_actions'] ?? [];
    $targetIndex    = null;

    foreach ($pendingActions as $idx => $action) {
      if (($action['action_id'] ?? '') === $actionId) {
        $targetIndex = $idx;
        break;
      }
    }

    if ($targetIndex === null) {
      return ['status' => false, 'message' => 'Action not found.'];
    }

    $action = $pendingActions[$targetIndex];
    if (($action['status'] ?? '') !== 'pending') {
      return ['status' => false, 'message' => 'Action has already been processed.'];
    }

    if (!$approved) {
      $pendingActions[$targetIndex]['status']      = 'cancelled';
      $pendingActions[$targetIndex]['resolved_at'] = now()->toDateTimeString();
      $metadata['pending_actions']                 = $pendingActions;
      $message->update(['metadata' => $metadata]);

      return ['status' => true, 'message' => 'Action cancelled.'];
    }

    $toolName = $action['tool'] ?? '';
    $tool     = $this->toolRegistry->getTool($toolName);

    if (!$tool || !method_exists($tool, 'executeConfirmed')) {
      return ['status' => false, 'message' => "Tool {$toolName} does not support confirmed execution."];
    }

    $execResult = $tool->executeConfirmed($action['arguments'] ?? [], $userId);

    $pendingActions[$targetIndex]['status']      = ($execResult['status'] ?? false) ? 'executed' : 'failed';
    $pendingActions[$targetIndex]['result']      = $execResult;
    $pendingActions[$targetIndex]['resolved_at'] = now()->toDateTimeString();
    $metadata['pending_actions']                 = $pendingActions;
    $message->update(['metadata' => $metadata]);

    return $execResult;
  }

  public function exportSessionMarkdown(AiAssistantSession $session): string
  {
    $messages = AiAssistantMessage::forSession($session->id)
      ->whereIn('role', ['user', 'assistant'])
      ->whereNotNull('content')
      ->oldest('id')
      ->get();

    $lines = [
      "# {$session->title}",
      '',
      '- **Exported At**: ' . now()->format('Y-m-d H:i:s'),
      '- **Total Tokens**: ' . number_format((int) $session->total_tokens_used),
      '- **Total Cost**: $' . number_format((float) $session->total_cost, 6),
      '',
      '---',
      '',
    ];

    foreach ($messages as $msg) {
      $roleLabel = $msg->role === 'user' ? 'User' : 'AI Assistant';
      $time      = $msg->created_at?->format('Y-m-d H:i') ?? '';
      $lines[]   = "### {$roleLabel} ({$time})";
      $lines[]   = '';
      $lines[]   = trim((string) $msg->content);
      $lines[]   = '';
    }

    return implode("\n", $lines);
  }

  public function exportSessionPdf(AiAssistantSession $session, int $userId): array
  {
    $user     = getUser($userId);
    $messages = AiAssistantMessage::forSession($session->id)
      ->whereIn('role', ['user', 'assistant'])
      ->whereNotNull('content')
      ->oldest('id')
      ->get();

    $mpdf = new Mpdf([
      'mode'          => 'utf-8',
      'format'        => 'A4',
      'margin_top'    => 15,
      'margin_bottom' => 15,
      'margin_left'   => 15,
      'margin_right'  => 15,
    ]);

    $titleEscaped = e($session->title);
    $exportedAt   = now()->format('Y-m-d H:i:s');

    $html = "
      <html>
        <head>
          <style>
            body { font-family: sans-serif; font-size: 11px; color: #1f2937; }
            h1 { font-size: 18px; margin-bottom: 4px; color: #111827; }
            .meta { font-size: 10px; color: #6b7280; margin-bottom: 16px; }
            .msg { margin-bottom: 14px; padding: 10px; border-radius: 6px; border: 1px solid #e5e7eb; }
            .msg-user { background-color: #eff6ff; border-color: #bfdbfe; }
            .msg-assistant { background-color: #f9fafb; }
            .role { font-weight: bold; margin-bottom: 4px; font-size: 10px; color: #374151; }
          </style>
        </head>
        <body>
          <h1>{$titleEscaped}</h1>
          <div class='meta'>Exported: {$exportedAt} | Tokens: " . number_format((int) $session->total_tokens_used) . "</div>
          <table style='width:100%; border-collapse: collapse;'><tbody>
    ";

    foreach ($messages as $msg) {
      $roleLabel = $msg->role === 'user' ? 'User' : 'AI Assistant';
      $cssClass  = $msg->role === 'user' ? 'msg-user' : 'msg-assistant';
      $time      = $msg->created_at?->format('Y-m-d H:i') ?? '';
      $bodyHtml  = Str::markdown((string) $msg->content);

      $html .= "
        <tr>
          <td style='padding-bottom: 10px;'>
            <div class='msg {$cssClass}'>
              <div class='role'>{$roleLabel} &bull; {$time}</div>
              <div>{$bodyHtml}</div>
            </div>
          </td>
        </tr>
      ";
    }

    $mpdf->WriteHTML($html);

    return makePdf($mpdf, $user, false, false, true);
  }

  private function generateLocalFallbackResponse(): string
  {
    return "### ⚠️ AI Assistant Service Unavailable\n\n" .
      "We apologize, but the **AI Assistant** system is currently experiencing issues or has not been properly configured.\n\n" .
      "- Please contact the **Technical Team** to verify and check the server configuration.\n" .
      "- Please check back periodically.\n\n" .
      "> If the issue persists, please reach out to your system administrator.";
  }
}
