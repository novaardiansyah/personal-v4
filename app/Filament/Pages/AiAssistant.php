<?php

namespace App\Filament\Pages;

use App\Models\AiAssistantContext;
use App\Models\AiAssistantMessage;
use App\Models\AiAssistantSession;
use App\Services\AiAssistant\AiAssistantService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class AiAssistant extends Page
{
  use WithFileUploads;

  protected static \BackedEnum | string | null $navigationIcon  = 'heroicon-o-sparkles';
  protected static string | UnitEnum | null     $navigationGroup = 'Productivity';
  protected static ?int                         $navigationSort  = 1;
  protected static ?string                      $title           = 'AI Assistant';
  protected static ?string                      $slug            = 'ai-assistant';

  protected string $view = 'filament.pages.ai-assistant';

  public string  $userMessage       = '';
  public string  $selectedModel     = '';
  public ?int    $selectedContextId = null;
  public string  $searchQuery       = '';
  public ?string $activeSessionId   = null;
  public array   $sessions          = [];
  public array   $messages          = [];
  public int     $messageLimit      = 30;
  public bool    $hasMoreMessages   = false;
  public ?string $editingSessionId  = null;
  public string  $editingTitle      = '';
  public bool    $isSidebarOpen     = true;
  public bool    $isGenerating      = false;
  public         $attachment        = null;

  public function mount(): void
  {
    $this->selectedModel = (string) config('services.ai_assistant.model');

    $user   = getUser();
    $userId = $user?->id;

    if ($userId) {
      $records = AiAssistantSession::forUser($userId)
        ->orderByDesc('is_pinned')
        ->latest('last_interacted_at')
        ->latest('updated_at')
        ->get();

      if ($records->isEmpty()) {
        $defaultContext = app(AiAssistantService::class)->resolveContext();
        $newSession     = AiAssistantSession::create([
          'user_id'            => $userId,
          'context_id'         => $defaultContext?->id,
          'uuid'               => uuid7(),
          'title'              => 'New Conversation',
          'last_interacted_at' => now(),
        ]);
        $records = collect([$newSession]);
      }

      $this->loadSessionsFromDatabase($records);
    }
  }

  public function getContextsProperty(): array
  {
    return AiAssistantContext::active()
      ->ordered()
      ->get()
      ->map(fn ($ctx) => [
        'id'              => $ctx->id,
        'name'            => $ctx->name,
        'description'     => $ctx->description,
        'default_model'   => $ctx->default_model,
        'starter_prompts' => $ctx->starter_prompts ?? [],
      ])
      ->toArray();
  }

  public function getActiveContextProperty(): ?array
  {
    $contexts = $this->contexts;
    foreach ($contexts as $ctx) {
      if ((int) $ctx['id'] === (int) $this->selectedContextId) {
        return $ctx;
      }
    }

    return $contexts[0] ?? null;
  }

  public function updatedSelectedContextId($value): void
  {
    $contextId = $value ? (int) $value : null;
    $user      = getUser();
    $userId    = $user?->id;

    if ($this->activeSessionId && isset($this->sessions[$this->activeSessionId])) {
      $this->sessions[$this->activeSessionId]['context_id'] = $contextId;

      if ($userId) {
        AiAssistantSession::forUser($userId)
          ->where('uuid', $this->activeSessionId)
          ->update(['context_id' => $contextId]);
      }
    }
  }

  public function updatedSelectedModel($value): void
  {
    $model  = trim((string) $value);
    $user   = getUser();
    $userId = $user?->id;

    if ($this->activeSessionId && isset($this->sessions[$this->activeSessionId])) {
      $this->sessions[$this->activeSessionId]['model_override'] = $model !== '' ? $model : null;

      if ($userId) {
        AiAssistantSession::forUser($userId)
          ->where('uuid', $this->activeSessionId)
          ->update(['model_override' => $model !== '' ? $model : null]);
      }
    }
  }

  public function createNewSession(): void
  {
    $user   = getUser();
    $userId = $user?->id;
    $count  = count($this->sessions) + 1;
    $title  = "Conversation #{$count}";

    if ($userId) {
      $contextId = $this->selectedContextId ?: app(AiAssistantService::class)->resolveContext()?->id;
      $record    = AiAssistantSession::create([
        'user_id'            => $userId,
        'context_id'         => $contextId,
        'uuid'               => uuid7(),
        'title'              => $title,
        'last_interacted_at' => now(),
      ]);

      $uuid                  = $record->uuid;
      $this->sessions        = array_merge([$uuid => $this->formatSessionSummary($record)], $this->sessions);
      $this->activeSessionId = $uuid;
      $this->selectedContextId = $contextId;
    }

    $this->messages        = [];
    $this->userMessage     = '';
    $this->hasMoreMessages = false;
    $this->messageLimit    = 30;

    Notification::make()
      ->title('New conversation started')
      ->success()
      ->duration(2000)
      ->send();
  }

  public function togglePinSession(string $sessionId): void
  {
    if (!isset($this->sessions[$sessionId])) {
      return;
    }

    $newPin = !($this->sessions[$sessionId]['is_pinned'] ?? false);
    $this->sessions[$sessionId]['is_pinned'] = $newPin;

    $user   = getUser();
    $userId = $user?->id;
    if ($userId) {
      AiAssistantSession::forUser($userId)
        ->where('uuid', $sessionId)
        ->update(['is_pinned' => $newPin]);
    }
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
    if (!isset($this->sessions[$sessionId])) {
      return;
    }

    $this->activeSessionId   = $sessionId;
    $this->messageLimit      = 30;
    $this->selectedContextId = $this->sessions[$sessionId]['context_id'] ?? null;
    $this->selectedModel     = $this->sessions[$sessionId]['model_override'] ?: (string) config('services.ai_assistant.model');
    $this->userMessage       = '';
    $this->attachment        = null;

    $this->loadActiveSessionMessages();
  }

  public function loadOlderMessages(): void
  {
    $this->messageLimit += 30;
    $this->loadActiveSessionMessages();
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
        $firstKey = array_key_first($this->sessions);
        $this->selectSession($firstKey);
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
    $this->createNewSession();
  }

  public function sendPrompt(string $promptText): void
  {
    $this->userMessage = $promptText;
    $this->sendMessage();
  }

  public function removeAttachment(): void
  {
    $this->attachment = null;
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

    $attachmentsPayload = [];
    if ($this->attachment) {
      $this->validate([
        'attachment' => 'image|max:5120',
      ]);

      $storedPath = $this->attachment->store('ai-assistant-attachments', 'public');
      if ($storedPath) {
        $attachmentsPayload[] = [
          'path' => $storedPath,
          'mime' => $this->attachment->getMimeType(),
          'name' => $this->attachment->getClientOriginalName(),
        ];
      }
      $this->attachment = null;
    }

    $userMsg = [
      'id'          => uuid7(),
      'role'        => 'user',
      'content'     => $trimmed,
      'attachments' => $attachmentsPayload,
      'created_at'  => now()->format('H:i'),
    ];

    $botPlaceholderMsg = [
      'id'             => uuid7(),
      'role'           => 'assistant',
      'content'        => '',
      'is_placeholder' => true,
      'created_at'     => now()->format('H:i'),
    ];

    $this->messages[] = $userMsg;
    $this->messages[] = $botPlaceholderMsg;

    $this->userMessage  = '';
    $this->isGenerating = true;

    $activeTitle = $this->sessions[$this->activeSessionId]['title'] ?? '';
    if (count($this->messages) <= 2 || str_starts_with($activeTitle, 'Conversation #') || $activeTitle === 'New Conversation') {
      $this->sessions[$this->activeSessionId]['title'] = Str::limit($trimmed, 40);
    }

    $this->dispatch('fetch-ai-response', [
      'prompt'         => $trimmed,
      'attachments'    => $attachmentsPayload,
      'skip_user_save' => false,
    ]);
  }

  #[On('fetch-ai-response')]
  public function processAiResponse(array $eventData = []): void
  {
    $prompt       = (string) ($eventData['prompt'] ?? '');
    $attachments  = $eventData['attachments'] ?? [];
    $skipUserSave = (bool) ($eventData['skip_user_save'] ?? false);

    if ($prompt === '') {
      for ($i = count($this->messages) - 1; $i >= 0; $i--) {
        if (($this->messages[$i]['role'] ?? '') === 'user') {
          $prompt = (string) ($this->messages[$i]['content'] ?? '');
          break;
        }
      }
    }

    $user   = getUser();
    $userId = $user?->id;
    $dbId   = $this->sessions[$this->activeSessionId]['db_id'] ?? null;

    if ($prompt === '' || !$userId || !$dbId) {
      $this->isGenerating = false;
      return;
    }

    $session = AiAssistantSession::forUser($userId)->find($dbId);
    if (!$session) {
      $this->isGenerating = false;
      return;
    }

    $service        = app(AiAssistantService::class);
    $streamedBuffer = '';

    $result = $service->sendMessage(
      $session,
      $userId,
      $prompt,
      [
        'model'          => $this->selectedModel,
        'context_id'     => $this->selectedContextId,
        'attachments'    => $attachments,
        'skip_user_save' => $skipUserSave,
      ],
      function (string $contentDelta, string $reasoningDelta) use (&$streamedBuffer): void {
        if ($contentDelta !== '') {
          $streamedBuffer .= $contentDelta;
          $this->stream(to: 'streaming-reply', content: e($streamedBuffer), replace: true);
        }
      },
      function (string $statusText): void {
        $this->stream(to: 'streaming-status', content: e($statusText), replace: true);
      }
    );

    $this->sessions[$this->activeSessionId] = $this->formatSessionSummary($session->fresh());
    $this->isGenerating                     = false;
    $this->loadActiveSessionMessages();
  }

  public function regenerateLastMessage(): void
  {
    if (empty($this->messages)) {
      return;
    }

    $user   = getUser();
    $userId = $user?->id;
    $dbId   = $this->sessions[$this->activeSessionId]['db_id'] ?? null;

    $lastMsg = end($this->messages);
    if (($lastMsg['role'] ?? '') === 'assistant') {
      $lastMsgId = $lastMsg['id'] ?? null;
      if ($lastMsgId && $userId && $dbId) {
        AiAssistantMessage::forSession($dbId)
          ->forUser($userId)
          ->where('uuid', $lastMsgId)
          ->delete();
      }
      array_pop($this->messages);
    }

    $lastUserMsg = end($this->messages);
    if (($lastUserMsg['role'] ?? '') !== 'user') {
      return;
    }

    $this->messages[] = [
      'id'             => uuid7(),
      'role'           => 'assistant',
      'content'        => '',
      'is_placeholder' => true,
      'created_at'     => now()->format('H:i'),
    ];

    $this->isGenerating = true;

    $this->dispatch('fetch-ai-response', [
      'prompt'         => $lastUserMsg['content'],
      'attachments'    => $lastUserMsg['attachments'] ?? [],
      'skip_user_save' => true,
    ]);
  }

  public function confirmAction(string $messageUuid, string $actionId): void
  {
    $this->resolvePendingAction($messageUuid, $actionId, true);
  }

  public function cancelAction(string $messageUuid, string $actionId): void
  {
    $this->resolvePendingAction($messageUuid, $actionId, false);
  }

  public function exportMarkdown(): ?StreamedResponse
  {
    $user   = getUser();
    $userId = $user?->id;
    $dbId   = $this->sessions[$this->activeSessionId]['db_id'] ?? null;

    if (!$userId || !$dbId) {
      return null;
    }

    $session = AiAssistantSession::forUser($userId)->find($dbId);
    if (!$session) {
      return null;
    }

    $markdown = app(AiAssistantService::class)->exportSessionMarkdown($session);
    $filename = Str::slug($session->title ?: 'conversation') . '.md';

    return response()->streamDownload(function () use ($markdown): void {
      echo $markdown;
    }, $filename, ['Content-Type' => 'text/markdown; charset=UTF-8']);
  }

  public function exportPdf(): void
  {
    $user   = getUser();
    $userId = $user?->id;
    $dbId   = $this->sessions[$this->activeSessionId]['db_id'] ?? null;

    if (!$userId || !$dbId) {
      return;
    }

    $session = AiAssistantSession::forUser($userId)->find($dbId);
    if (!$session) {
      return;
    }

    $result = app(AiAssistantService::class)->exportSessionPdf($session, $userId);
    $url    = $result['signed_url'] ?? null;

    if ($url) {
      Notification::make()
        ->title('Conversation PDF ready')
        ->body("Exported {$result['filename']}")
        ->success()
        ->actions([
          \Filament\Actions\Action::make('download')
            ->label('Download PDF')
            ->url($url, shouldOpenInNewTab: true),
        ])
        ->send();
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

  public function getGroupedSessionsProperty(): array
  {
    $groups = [
      'Pinned'          => [],
      'Today'           => [],
      'Yesterday'       => [],
      'Previous 7 Days' => [],
      'Older'           => [],
    ];

    foreach ($this->filteredSessions as $session) {
      if (!empty($session['is_pinned'])) {
        $groups['Pinned'][] = $session;
        continue;
      }

      $rawDate = $session['last_interacted_raw'] ?? null;
      if (!$rawDate) {
        $groups['Older'][] = $session;
        continue;
      }

      $dt = Carbon::parse($rawDate);
      if ($dt->isToday()) {
        $groups['Today'][] = $session;
      } elseif ($dt->isYesterday()) {
        $groups['Yesterday'][] = $session;
      } elseif ($dt->greaterThanOrEqualTo(now()->subDays(7))) {
        $groups['Previous 7 Days'][] = $session;
      } else {
        $groups['Older'][] = $session;
      }
    }

    return array_filter($groups, fn ($items) => !empty($items));
  }

  private function resolvePendingAction(string $messageUuid, string $actionId, bool $approved): void
  {
    $user   = getUser();
    $userId = $user?->id;
    $dbId   = $this->sessions[$this->activeSessionId]['db_id'] ?? null;

    if (!$userId || !$dbId) {
      return;
    }

    $session = AiAssistantSession::forUser($userId)->find($dbId);
    if (!$session) {
      return;
    }

    $result = app(AiAssistantService::class)->confirmPendingAction($session, $userId, $messageUuid, $actionId, $approved);
    $this->loadActiveSessionMessages();

    Notification::make()
      ->title($result['message'] ?? ($approved ? 'Action executed' : 'Action cancelled'))
      ->status(($result['status'] ?? false) ? 'success' : 'danger')
      ->send();
  }

  private function loadSessionsFromDatabase($records): void
  {
    $this->sessions = [];
    foreach ($records as $record) {
      $this->sessions[$record->uuid] = $this->formatSessionSummary($record);
    }

    if (!empty($this->sessions)) {
      $firstKey                = array_key_first($this->sessions);
      $this->activeSessionId   = $firstKey;
      $this->selectedContextId = $this->sessions[$firstKey]['context_id'] ?? null;
      $this->selectedModel     = $this->sessions[$firstKey]['model_override'] ?: (string) config('services.ai_assistant.model');
      $this->loadActiveSessionMessages();
    }
  }

  private function formatSessionSummary(AiAssistantSession $record): array
  {
    $interactedAt = $record->last_interacted_at ?? $record->updated_at;

    return [
      'id'                  => $record->uuid,
      'db_id'               => $record->id,
      'context_id'          => $record->context_id,
      'model_override'      => $record->model_override,
      'is_pinned'           => (bool) $record->is_pinned,
      'title'               => $record->title,
      'total_tokens_used'   => (int) $record->total_tokens_used,
      'total_cost'          => (float) $record->total_cost,
      'last_interacted_at'  => $interactedAt?->format('H:i'),
      'last_interacted_raw' => $interactedAt?->toDateTimeString(),
      'created_at'          => $record->created_at?->format('H:i'),
      'updated_at'          => $record->updated_at?->format('H:i'),
    ];
  }

  private function loadActiveSessionMessages(): void
  {
    $dbId = $this->sessions[$this->activeSessionId]['db_id'] ?? null;
    if (!$dbId) {
      $this->messages        = [];
      $this->hasMoreMessages = false;
      return;
    }

    $baseQuery = AiAssistantMessage::forSession($dbId)
      ->whereIn('role', ['user', 'assistant'])
      ->whereNotNull('content')
      ->where('content', '!=', '');

    $totalCount            = (clone $baseQuery)->count();
    $this->hasMoreMessages = $totalCount > $this->messageLimit;

    $records = $baseQuery
      ->latest('id')
      ->take($this->messageLimit)
      ->get()
      ->reverse()
      ->values();

    $this->messages = $records->map(function ($msg) {
      $metadata      = $msg->metadata ?? [];
      $executedTools = collect($metadata['executed_tools'] ?? [])
        ->pluck('name')
        ->unique()
        ->values()
        ->toArray();

      return [
        'id'                => $msg->uuid,
        'db_id'             => $msg->id,
        'role'              => $msg->role,
        'content'           => $msg->content,
        'reasoning_content' => $msg->reasoning_content,
        'model_used'        => $msg->model_used,
        'token_total'       => (int) $msg->token_total,
        'latency_ms'        => (int) $msg->latency_ms,
        'cost'              => (float) $msg->cost,
        'status'            => $msg->status,
        'executed_tools'    => $executedTools,
        'pending_actions'   => $metadata['pending_actions'] ?? [],
        'attachments'       => $metadata['attachments'] ?? [],
        'created_at'        => $msg->created_at?->format('H:i') ?? now()->format('H:i'),
      ];
    })->toArray();
  }
}
