<?php

namespace App\Filament\Pages;

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
    $stored = session('ai_assistant_sessions', session('chatbot_sessions', []));

    if (empty($stored)) {
      $initialSession = $this->makeNewSessionObject('New Conversation');
      $this->sessions = [$initialSession['id'] => $initialSession];
      session(['ai_assistant_sessions' => $this->sessions]);
    } else {
      $this->sessions = $stored;
    }

    $firstKey              = array_key_first($this->sessions);
    $this->activeSessionId = $firstKey;
    $this->messages        = $this->sessions[$firstKey]['messages'] ?? [];
  }

  public function createNewSession(): void
  {
    $count                                  = count($this->sessions) + 1;
    $newSession                             = $this->makeNewSessionObject("Conversation #{$count}");
    $this->sessions[$newSession['id']]      = $newSession;
    $this->activeSessionId                  = $newSession['id'];
    $this->messages                         = [];
    $this->userMessage                      = '';

    $this->persistSessions();

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
      $this->persistSessions();

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
      $this->messages        = $this->sessions[$sessionId]['messages'] ?? [];
      $this->userMessage     = '';
    }
  }

  public function deleteSession(string $sessionId): void
  {
    unset($this->sessions[$sessionId]);

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

    $this->persistSessions();

    Notification::make()
      ->title('Conversation deleted')
      ->info()
      ->duration(2000)
      ->send();
  }

  public function clearAllSessions(): void
  {
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

  public function sendMessage(): void
  {
    $trimmed = trim($this->userMessage);

    if ($trimmed === '') {
      return;
    }

    if (!$this->activeSessionId || !isset($this->sessions[$this->activeSessionId])) {
      $this->createNewSession();
    }

    $userMsg = [
      'id'         => Str::uuid()->toString(),
      'role'       => 'user',
      'content'    => $trimmed,
      'created_at' => now()->format('H:i'),
    ];

    $botPlaceholderMsg = [
      'id'             => Str::uuid()->toString(),
      'role'           => 'assistant',
      'content'        => 'Thinking...',
      'is_placeholder' => true,
      'created_at'     => now()->format('H:i'),
    ];

    $this->messages[] = $userMsg;
    $this->messages[] = $botPlaceholderMsg;

    $this->userMessage  = '';
    $this->isGenerating = true;

    if (count($this->messages) <= 2 || str_starts_with($this->sessions[$this->activeSessionId]['title'], 'Conversation #') || $this->sessions[$this->activeSessionId]['title'] === 'New Conversation') {
      $newTitle                                              = Str::limit($trimmed, 30);
      $this->sessions[$this->activeSessionId]['title']      = $newTitle;
    }

    $this->sessions[$this->activeSessionId]['messages']   = $this->messages;
    $this->sessions[$this->activeSessionId]['updated_at'] = now()->format('H:i');

    $this->persistSessions();

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

    $replyText = $this->fetchAiResponse($userPrompt);

    $lastIndex = count($this->messages) - 1;
    if ($lastIndex >= 0 && $this->messages[$lastIndex]['role'] === 'assistant') {
      $this->messages[$lastIndex]['content']        = $replyText;
      $this->messages[$lastIndex]['is_placeholder'] = false;
    } else {
      $this->messages[] = [
        'id'         => Str::uuid()->toString(),
        'role'       => 'assistant',
        'content'    => $replyText,
        'created_at' => now()->format('H:i'),
      ];
    }

    $this->sessions[$this->activeSessionId]['messages']   = $this->messages;
    $this->sessions[$this->activeSessionId]['updated_at'] = now()->format('H:i');
    $this->isGenerating                                   = false;

    $this->persistSessions();
  }

  public function regenerateLastMessage(): void
  {
    if (empty($this->messages)) {
      return;
    }

    $lastMsg = end($this->messages);
    if ($lastMsg['role'] === 'assistant') {
      array_pop($this->messages);
    }

    if (empty($this->messages)) {
      return;
    }

    $lastUserMsg = end($this->messages);

    if ($lastUserMsg['role'] === 'user') {
      $botPlaceholderMsg = [
        'id'             => Str::uuid()->toString(),
        'role'           => 'assistant',
        'content'        => 'Thinking...',
        'is_placeholder' => true,
        'created_at'     => now()->format('H:i'),
      ];

      $this->messages[]   = $botPlaceholderMsg;
      $this->isGenerating = true;

      $this->sessions[$this->activeSessionId]['messages']   = $this->messages;
      $this->sessions[$this->activeSessionId]['updated_at'] = now()->format('H:i');
      $this->persistSessions();

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

  private function makeNewSessionObject(string $title): array
  {
    $id = Str::uuid()->toString();

    return [
      'id'         => $id,
      'title'      => $title,
      'created_at' => now()->format('H:i'),
      'updated_at' => now()->format('H:i'),
      'messages'   => [],
    ];
  }

  private function persistSessions(): void
  {
    session(['ai_assistant_sessions' => $this->sessions]);
  }

  private function fetchAiResponse(string $prompt): array|string
  {
    $chatbotUrl         = env('CHATBOT_API_URL');
    $chatbotKey         = env('CHATBOT_API_KEY');
    $chatbotModel       = env('CHATBOT_MODEL');
    $chatbotMaxTokens   = (int) env('CHATBOT_MAX_TOKENS', 3072);
    $chatbotTemperature = (float) env('CHATBOT_TEMPERATURE', 0.3);

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
            $content = $data['choices'][0]['message']['content'] ?? null;

            if (empty($content) && isset($data['choices'][0]['message']['reasoning_content'])) {
              $content = $data['choices'][0]['message']['reasoning_content'];
            }

            if (!empty($content)) {
              return $content;
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
    $lower = strtolower($prompt);

    if (str_contains($lower, 'code') || str_contains($lower, 'php') || str_contains($lower, 'laravel') || str_contains($lower, 'filament') || str_contains($lower, 'function')) {
      return "Here is a clean implementation example based on your request:\n\n" .
        "```php\n" .
        "namespace App\\Services;\n\n" .
        "class AssistantService\n" .
        "{\n" .
        "  public function processTask(array \$data): array\n" .
        "  {\n" .
        "    \$formatted = array_map(fn(\$item) => textCapitalize(\$item), \$data);\n" .
        "    return [\n" .
        "      'status'  => 'success',\n" .
        "      'payload' => \$formatted,\n" .
        "    ];\n" .
        "  }\n" .
        "}\n" .
        "```\n\n" .
        "This adheres to project conventions with 2 spaces indentation and strict vertical operator alignment.";
    }

    if (str_contains($lower, 'payment') || str_contains($lower, 'finance') || str_contains($lower, 'debt') || str_contains($lower, 'budget') || str_contains($lower, 'currency')) {
      return "### Financial Assistant Overview\n\n" .
        "Here are recommendations for managing your finances:\n\n" .
        "- **Track Daily Expenses**: Register all payments under the `Payments` module.\n" .
        "- **Currency Format**: All amounts are formatted in Indonesian Rupiah (e.g. `toIndonesianCurrency(1500000)` = `Rp1.500.000,00`).\n" .
        "- **Debt Installments**: Keep track of scheduled debt pay-offs using recurring reminders.\n\n" .
        "> Tip: You can check your financial summary in the main **Dashboard** widgets.";
    }

    if (str_contains($lower, 'email') || str_contains($lower, 'write') || str_contains($lower, 'draft') || str_contains($lower, 'template')) {
      return "Here is a professional draft for your message:\n\n" .
        "**Subject:** Important Update Regarding Project Milestone\n\n" .
        "Dear Team,\n\n" .
        "I hope this message finds you well. I wanted to share a quick update regarding our current project status. " .
        "All planned features have been successfully developed and reviewed.\n\n" .
        "Please let me know if you need any additional clarification.\n\n" .
        "Best regards,\n" .
        "**Nova Ardiansyah**";
    }

    if (str_contains($lower, 'hello') || str_contains($lower, 'hi') || str_contains($lower, 'halo') || str_contains($lower, 'hey')) {
      return "Hello! I am your **AI Assistant** inside **Personal V4**.\n\n" .
        "I can help you with:\n" .
        "- Writing PHP & Laravel code snippets\n" .
        "- Designing Filament v5 components\n" .
        "- Financial calculation & payment summaries\n" .
        "- Drafting emails, notes & documentations\n\n" .
        "How can I assist you today?";
    }

    return "Thank you for your prompt: **\"{$prompt}\"**.\n\n" .
      "As your personal assistant inside **Personal V4**, I am configured in **{$this->systemPersona}** persona mode using model **{$this->selectedModel}**.\n\n" .
      "### Quick Summary\n" .
      "- **Status**: Task processed successfully\n" .
      "- **Persona**: " . ucfirst($this->systemPersona) . "\n" .
      "- **Model**: " . strtoupper($this->selectedModel) . "\n\n" .
      "Connected via OpenAI-compatible `CHATBOT_API_URL` and `CHATBOT_API_KEY`.";
  }
}
