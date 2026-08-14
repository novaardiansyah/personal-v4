<?php

namespace App\Filament\Pages;

use App\Models\AiAssistantMessage;
use App\Models\AiAssistantSession;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use UnitEnum;

class AiAssistant extends Page
{
  protected static \BackedEnum | string | null $navigationIcon  = 'heroicon-o-sparkles';
  protected static string | UnitEnum | null     $navigationGroup = 'Productivity';
  protected static ?int                         $navigationSort  = 1;
  protected static ?string                      $title           = 'AI Assistant';
  protected static ?string                      $slug            = 'ai-assistant';

  protected string $view = 'filament.pages.ai-assistant';

  public string  $userMessage       = '';
  public string  $selectedModel     = 'gpt-4o';
  public string  $systemPersona     = 'general';
  public string  $searchQuery       = '';
  public ?string $activeSessionId   = null;
  public array   $sessions          = [];
  public array   $messages          = [];
  public ?string $editingSessionId = null;
  public string  $editingTitle       = '';
  public bool    $isSidebarOpen     = true;
  public bool    $isGenerating      = false;

  public function mount(): void
  {
    $user   = getUser();
    $userId = $user?->id;

    if ($userId) {
      $records = AiAssistantSession::forUser($userId)
        ->latest('last_interacted_at')
        ->latest('updated_at')
        ->get();

      if ($records->isEmpty()) {
        $newSession = AiAssistantSession::create([
          'user_id'            => $userId,
          'uuid'               => uuid7(),
          'title'              => 'New Conversation',
          'last_interacted_at' => now(),
        ]);
        $records = collect([$newSession]);
      }
    } else {
      $records = collect();
    }

    $this->loadSessionsFromDatabase($records);
  }

  public function createNewSession(): void
  {
    $user   = getUser();
    $userId = $user?->id;
    $count  = count($this->sessions) + 1;
    $title  = "Conversation #{$count}";

    if ($userId) {
      $record = AiAssistantSession::create([
        'user_id'            => $userId,
        'uuid'               => uuid7(),
        'title'              => $title,
        'last_interacted_at' => now(),
      ]);

      $uuid = $record->uuid;
      $this->sessions[$uuid] = [
        'id'                 => $uuid,
        'db_id'              => $record->id,
        'title'              => $record->title,
        'messages'           => [],
        'total_tokens_used'  => 0,
        'last_interacted_at' => now()->format('H:i'),
        'created_at'         => now()->format('H:i'),
        'updated_at'         => now()->format('H:i'),
      ];
      $this->activeSessionId = $uuid;
    } else {
      $uuid = uuid7();
      $newSession = [
        'id'         => $uuid,
        'title'      => $title,
        'messages'   => [],
        'created_at' => now()->format('H:i'),
        'updated_at' => now()->format('H:i'),
      ];
      $this->sessions[$uuid] = $newSession;
      $this->activeSessionId = $uuid;
    }

    $this->messages    = [];
    $this->userMessage = '';

    Notification::make()
      ->title('New conversation started')
      ->success()
      ->duration(2000)
      ->send();
  }

  public function editSession(string $sessionId): void
  {
    if (isset($this->sessions[$sessionId])) {
      $this->editingSessionId = $sessionId;
      $this->editingTitle     = $this->sessions[$sessionId]['title'] ?? '';
    }
  }

  public function saveSessionTitle(): void
  {
    $trimmed = trim($this->editingTitle);

    if ($trimmed !== '' && $this->editingSessionId && isset($this->sessions[$this->editingSessionId])) {
      $this->sessions[$this->editingSessionId]['title'] = $trimmed;

      $user   = getUser();
      $userId = $user?->id;
      if ($userId) {
        AiAssistantSession::forUser($userId)
          ->where('uuid', $this->editingSessionId)
          ->update(['title' => $trimmed]);
      }

      Notification::make()
        ->title('Title updated')
        ->success()
        ->duration(2000)
        ->send();
    }

    $this->editingSessionId = null;
    $this->editingTitle     = '';
  }

  public function cancelEditSession(): void
  {
    $this->editingSessionId = null;
    $this->editingTitle     = '';
  }

  public function selectSession(string $sessionId): void
  {
    if (isset($this->sessions[$sessionId])) {
      $this->activeSessionId = $sessionId;
      $dbId = $this->sessions[$sessionId]['db_id'] ?? null;

      if ($dbId) {
        $messages = AiAssistantMessage::forSession($dbId)
          ->oldest()
          ->get()
          ->map(function ($msg) {
            return [
              'id'                => $msg->uuid,
              'db_id'             => $msg->id,
              'role'              => $msg->role,
              'content'           => $msg->content,
              'reasoning_content' => $msg->reasoning_content,
              'model_used'        => $msg->model_used,
              'status'            => $msg->status,
              'created_at'        => $msg->created_at?->format('H:i') ?? now()->format('H:i'),
            ];
          })
          ->toArray();

        $this->sessions[$sessionId]['messages'] = $messages;
      }

      $this->messages    = $this->sessions[$sessionId]['messages'] ?? [];
      $this->userMessage = '';
    }
  }

  public function deleteSession(string $sessionId): void
  {
    $dbSessionId = $this->sessions[$sessionId]['db_id'] ?? null;
    unset($this->sessions[$sessionId]);

    $user   = getUser();
    $userId = $user?->id;
    if ($userId) {
      if ($dbSessionId) {
        AiAssistantMessage::where('session_id', $dbSessionId)->delete();
      }

      AiAssistantSession::forUser($userId)
        ->where('uuid', $sessionId)
        ->delete();
    }

    if ($this->activeSessionId === $sessionId) {
      if (!empty($this->sessions)) {
        $firstKey              = array_key_first($this->sessions);
        $this->activeSessionId = $firstKey;
        $this->messages        = $this->sessions[$firstKey]['messages'] ?? [];
      } else {
        $this->createNewSession();
        return;
      }
    }

    Notification::make()
      ->title('Conversation deleted')
      ->info()
      ->duration(2000)
      ->send();
  }

  public function clearAllSessions(): void
  {
    $user   = getUser();
    $userId = $user?->id;

    if ($userId) {
      AiAssistantMessage::forUser($userId)->delete();
      AiAssistantSession::forUser($userId)->delete();
    }

    $this->sessions        = [];
    $this->messages        = [];
    $this->activeSessionId = null;

    session()->forget('ai_assistant_sessions');
    session()->forget('chatbot_sessions');
    $this->createNewSession();
  }

  public function sendPrompt(string $promptText): void
  {
    $this->userMessage = $promptText;
    $this->sendMessage();
  }

  public function sendMessage(?string $message = null): void
  {
    if ($message !== null && trim($message) !== '') {
      $this->userMessage = $message;
    }

    $trimmed = trim($this->userMessage);

    if ($trimmed === '') {
      return;
    }

    if (!$this->activeSessionId || !isset($this->sessions[$this->activeSessionId])) {
      $this->createNewSession();
    }

    $activeSession = $this->sessions[$this->activeSessionId];
    $dbSessionId   = $activeSession['db_id'] ?? null;
    $user          = getUser();
    $userId        = $user?->id;

    $userMsgUuid = uuid7();
    if ($dbSessionId && $userId) {
      AiAssistantMessage::create([
        'uuid'       => $userMsgUuid,
        'session_id' => $dbSessionId,
        'user_id'    => $userId,
        'role'       => 'user',
        'content'    => $trimmed,
        'model_used' => $this->selectedModel,
        'status'     => 'completed',
      ]);
    }

    $userMsg = [
      'id'         => $userMsgUuid,
      'role'       => 'user',
      'content'    => $trimmed,
      'created_at' => now()->format('H:i'),
    ];

    $botPlaceholderMsg = [
      'id'             => uuid7(),
      'role'           => 'assistant',
      'content'        => 'Thinking...',
      'is_placeholder' => true,
      'created_at'     => now()->format('H:i'),
    ];

    $this->messages[] = $userMsg;
    $this->messages[] = $botPlaceholderMsg;

    $this->userMessage  = '';
    $this->isGenerating = true;

    $newTitle = null;
    if (count($this->messages) <= 2 || str_starts_with($activeSession['title'], 'Conversation #') || $activeSession['title'] === 'New Conversation') {
      $newTitle = Str::limit($trimmed, 30);
    }

    $this->persistActiveSessionMessages($newTitle);

    $this->dispatch('fetch-ai-response');
  }

  #[On('fetch-ai-response')]
  public function processAiResponse(): void
  {
    if (empty($this->messages)) {
      return;
    }

    $userPrompt = '';
    for ($i = count($this->messages) - 1; $i >= 0; $i--) {
      if ($this->messages[$i]['role'] === 'user') {
        $userPrompt = $this->messages[$i]['content'];
        break;
      }
    }

    if (empty($userPrompt)) {
      $this->isGenerating = false;
      return;
    }

    $startTime = microtime(true);
    $reply     = $this->fetchAiResponse($userPrompt);
    $latencyMs = (int) round((microtime(true) - $startTime) * 1000);

    $replyText        = is_array($reply) ? ($reply['content'] ?? '') : $reply;
    $reasoningContent = is_array($reply) ? ($reply['reasoning_content'] ?? null) : null;
    $modelUsed        = is_array($reply) ? ($reply['model_used'] ?? $this->selectedModel) : $this->selectedModel;
    $tokenPrompt      = is_array($reply) ? ($reply['token_prompt'] ?? 0) : 0;
    $tokenCompletion  = is_array($reply) ? ($reply['token_completion'] ?? 0) : 0;
    $tokenTotal       = is_array($reply) ? ($reply['token_total'] ?? 0) : 0;
    $cost             = is_array($reply) ? (float) ($reply['cost'] ?? 0) : 0;
    $metadata         = is_array($reply) ? ($reply['metadata'] ?? null) : null;

    $activeSession = $this->sessions[$this->activeSessionId] ?? [];
    $dbSessionId   = $activeSession['db_id'] ?? null;
    $user          = getUser();
    $userId        = $user?->id;

    $botMsgUuid = uuid7();
    if ($dbSessionId && $userId) {
      AiAssistantMessage::create([
        'uuid'              => $botMsgUuid,
        'session_id'        => $dbSessionId,
        'user_id'           => $userId,
        'role'              => 'assistant',
        'content'           => $replyText,
        'reasoning_content' => $reasoningContent,
        'token_prompt'      => $tokenPrompt,
        'token_completion'  => $tokenCompletion,
        'token_total'       => $tokenTotal,
        'latency_ms'        => $latencyMs,
        'cost'              => $cost,
        'model_used'        => $modelUsed,
        'status'            => 'completed',
        'metadata'          => $metadata,
      ]);

      if ($tokenTotal > 0) {
        AiAssistantSession::where('id', $dbSessionId)->increment('total_tokens_used', $tokenTotal);
      }
    }

    $lastIndex = count($this->messages) - 1;
    if ($lastIndex >= 0 && $this->messages[$lastIndex]['role'] === 'assistant') {
      $this->messages[$lastIndex]['id']             = $botMsgUuid;
      $this->messages[$lastIndex]['content']        = $replyText;
      $this->messages[$lastIndex]['is_placeholder'] = false;
    } else {
      $this->messages[] = [
        'id'         => $botMsgUuid,
        'role'       => 'assistant',
        'content'    => $replyText,
        'created_at' => now()->format('H:i'),
      ];
    }

    $this->isGenerating = false;
    $this->persistActiveSessionMessages();
  }

  public function regenerateLastMessage(): void
  {
    if (empty($this->messages)) {
      return;
    }

    $lastMsg = end($this->messages);
    if ($lastMsg['role'] === 'assistant') {
      $lastMsgId = $lastMsg['id'] ?? null;
      if ($lastMsgId) {
        AiAssistantMessage::where('uuid', $lastMsgId)->delete();
      }
      array_pop($this->messages);
    }

    if (empty($this->messages)) {
      return;
    }

    $lastUserMsg = end($this->messages);

    if ($lastUserMsg['role'] === 'user') {
      $botPlaceholderMsg = [
        'id'             => uuid7(),
        'role'           => 'assistant',
        'content'        => 'Thinking...',
        'is_placeholder' => true,
        'created_at'     => now()->format('H:i'),
      ];

      $this->messages[]   = $botPlaceholderMsg;
      $this->isGenerating = true;

      $this->persistActiveSessionMessages();

      $this->dispatch('fetch-ai-response');
    }
  }

  public function toggleSidebar(): void
  {
    $this->isSidebarOpen = !$this->isSidebarOpen;
  }

  public function getFilteredSessionsProperty(): array
  {
    if (trim($this->searchQuery) === '') {
      return $this->sessions;
    }

    return array_filter($this->sessions, function ($session) {
      return str_contains(strtolower($session['title']), strtolower($this->searchQuery));
    });
  }

  private function loadSessionsFromDatabase($records): void
  {
    $this->sessions = [];
    foreach ($records as $record) {
      $uuid = $record->uuid;

      $messages = $record->messages()
        ->oldest()
        ->get()
        ->map(function ($msg) {
          return [
            'id'                => $msg->uuid,
            'db_id'             => $msg->id,
            'role'              => $msg->role,
            'content'           => $msg->content,
            'reasoning_content' => $msg->reasoning_content,
            'model_used'        => $msg->model_used,
            'status'            => $msg->status,
            'created_at'        => $msg->created_at?->format('H:i') ?? now()->format('H:i'),
          ];
        })
        ->toArray();

      $this->sessions[$uuid] = [
        'id'                 => $uuid,
        'db_id'              => $record->id,
        'title'              => $record->title,
        'messages'           => $messages,
        'total_tokens_used'  => $record->total_tokens_used,
        'last_interacted_at' => $record->last_interacted_at?->format('H:i') ?? $record->updated_at?->format('H:i'),
        'created_at'         => $record->created_at?->format('H:i'),
        'updated_at'         => $record->updated_at?->format('H:i'),
      ];
    }

    if (!empty($this->sessions)) {
      $firstKey              = array_key_first($this->sessions);
      $this->activeSessionId = $firstKey;
      $this->messages        = $this->sessions[$firstKey]['messages'] ?? [];
    }
  }

  private function persistActiveSessionMessages(?string $title = null): void
  {
    if (!$this->activeSessionId || !isset($this->sessions[$this->activeSessionId])) {
      return;
    }

    $this->sessions[$this->activeSessionId]['messages']   = $this->messages;
    $this->sessions[$this->activeSessionId]['updated_at'] = now()->format('H:i');

    if ($title !== null) {
      $this->sessions[$this->activeSessionId]['title'] = $title;
    }

    $user   = getUser();
    $userId = $user?->id;

    if ($userId) {
      $updateData = [
        'last_interacted_at' => now(),
      ];

      if ($title !== null) {
        $updateData['title'] = $title;
      }

      AiAssistantSession::forUser($userId)
        ->where('uuid', $this->activeSessionId)
        ->update($updateData);
    }
  }

  private function fetchAiResponse(string $prompt): array|string
  {
    $chatbotUrl         = config('services.ai_assistant.api_url');
    $chatbotKey         = config('services.ai_assistant.api_key');
    $chatbotModel       = config('services.ai_assistant.model');
    $chatbotMaxTokens   = (int) config('services.ai_assistant.max_tokens', 3072);
    $chatbotTemperature = (float) config('services.ai_assistant.temperature', 0.3);

    if (!empty($chatbotKey) && !empty($chatbotUrl)) {
      try {
        $endpoint = rtrim($chatbotUrl, '/') . '/chat/completions';
        $payload  = [
          'model'       => $chatbotModel,
          'max_tokens'  => $chatbotMaxTokens,
          'temperature' => $chatbotTemperature,
          'messages'    => array_map(fn($m) => [
            'role'    => $m['role'],
            'content' => $m['content'],
          ], array_slice($this->messages, -10)),
        ];

        $response = Http::withToken($chatbotKey)
          ->timeout(30)
          ->post($endpoint, $payload);

        if ($response->successful()) {
          $rawBody     = $response->body();
          $cleanedJson = $rawBody;

          if (preg_match('/\{[\s\S]*\}/', $rawBody, $matches)) {
            $cleanedJson = $matches[0];
          }

          $data = json_decode($cleanedJson, true);

          if (is_array($data)) {
            $content          = $data['choices'][0]['message']['content'] ?? null;
            $reasoningContent = $data['choices'][0]['message']['reasoning'] ?? $data['choices'][0]['message']['reasoning_content'] ?? null;

            if (empty($content) && !empty($reasoningContent)) {
              $content = $reasoningContent;
            }

            if (!empty($content)) {
              $usage         = $data['usage'] ?? [];
              $returnedModel = $data['model'] ?? $chatbotModel;
              $cost          = (float) ($data['cost'] ?? 0);

              $metadata = array_filter([
                'response_id'               => $data['id'] ?? null,
                'finish_reason'             => $data['choices'][0]['finish_reason'] ?? null,
                'cost'                      => $data['cost'] ?? null,
                'prompt_tokens_details'     => $usage['prompt_tokens_details'] ?? null,
                'completion_tokens_details' => $usage['completion_tokens_details'] ?? null,
              ], fn($val) => $val !== null);

              return [
                'content'           => $content,
                'reasoning_content' => $reasoningContent,
                'model_used'        => $returnedModel,
                'token_prompt'      => $usage['prompt_tokens'] ?? 0,
                'token_completion'  => $usage['completion_tokens'] ?? 0,
                'token_total'       => $usage['total_tokens'] ?? 0,
                'cost'              => $cost,
                'metadata'          => !empty($metadata) ? $metadata : null,
              ];
            }
          }
        }
      } catch (\Throwable $e) {
      }
    }

    return $this->generateLocalFallbackResponse($prompt);
  }

  private function generateLocalFallbackResponse(string $prompt): string
  {
    return "### ⚠️ AI Assistant Service Unavailable\n\n" .
      "We apologize, but the **AI Assistant** system is currently experiencing issues or has not been properly configured.\n\n" .
      "- Please contact the **Technical Team** to verify and check the server configuration.\n" .
      "- Please check back periodically.\n\n" .
      "> If the issue persists, please reach out to your system administrator.";
  }
}

