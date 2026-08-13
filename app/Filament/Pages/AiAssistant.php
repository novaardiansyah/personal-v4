<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
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

    $this->messages[]  = $userMsg;
    $this->userMessage  = '';
    $this->isGenerating = true;

    if (count($this->messages) === 1 || str_starts_with($this->sessions[$this->activeSessionId]['title'], 'Conversation #') || $this->sessions[$this->activeSessionId]['title'] === 'New Conversation') {
      $newTitle                                              = Str::limit($trimmed, 30);
      $this->sessions[$this->activeSessionId]['title']      = $newTitle;
    }

    $replyText = $this->fetchAiResponse($trimmed);

    $botMsg = [
      'id'         => Str::uuid()->toString(),
      'role'       => 'assistant',
      'content'    => $replyText,
      'created_at' => now()->format('H:i'),
    ];

    $this->messages[]                                           = $botMsg;
    $this->sessions[$this->activeSessionId]['messages']        = $this->messages;
    $this->sessions[$this->activeSessionId]['updated_at']      = now()->format('H:i');
    $this->isGenerating                                         = false;

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
      $this->isGenerating = true;
      $replyText          = $this->fetchAiResponse($lastUserMsg['content']);

      $botMsg = [
        'id'         => Str::uuid()->toString(),
        'role'       => 'assistant',
        'content'    => $replyText,
        'created_at' => now()->format('H:i'),
      ];

      $this->messages[]                                           = $botMsg;
      $this->sessions[$this->activeSessionId]['messages']        = $this->messages;
      $this->sessions[$this->activeSessionId]['updated_at']      = now()->format('H:i');
      $this->isGenerating                                         = false;

      $this->persistSessions();
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
    $openAiKey = getSetting('openai_api_key', env('OPENAI_API_KEY'));
    $geminiKey = getSetting('gemini_api_key', env('GEMINI_API_KEY'));

    if (!empty($openAiKey)) {
      try {
        $response = Http::withToken($openAiKey)
          ->timeout(20)
          ->post('https://api.openai.com/v1/chat/completions', [
            'model'    => $this->selectedModel === 'gpt-4o' ? 'gpt-4o' : 'gpt-3.5-turbo',
            'messages' => array_map(fn($m) => [
              'role'    => $m['role'],
              'content' => $m['content'],
            ], array_slice($this->messages, -10)),
          ]);

        if ($response->successful()) {
          $data = $response->json();
          return $data['choices'][0]['message']['content'] ?? 'No response received.';
        }
      } catch (\Throwable $e) {
      }
    }

    if (!empty($geminiKey)) {
      try {
        $response = Http::timeout(20)
          ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$geminiKey}", [
            'contents' => [
              [
                'parts' => [['text' => $prompt]],
              ],
            ],
          ]);

        if ($response->successful()) {
          $data = $response->json();
          return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'No response generated.';
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
      "You can configure an API key (`OPENAI_API_KEY` or `GEMINI_API_KEY`) in your settings to connect directly to live cloud models!";
  }
}
