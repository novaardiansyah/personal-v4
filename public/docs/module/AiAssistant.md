# AI Assistant Module Documentation

*Version: v1.0*  
*Last Updated: November 27, 2026*

## 1. Introduction

The **AI Assistant** module (`/admin/ai-assistant`) is an interactive, database-backed conversational AI workspace built as a custom Filament v5 Page inside the admin panel. It connects to any OpenAI-compatible Chat Completions endpoint and supports **multi-step tool calling (function calling)** so the assistant can query real user financial data (payment transactions and account balances) directly from the application database.

**Key distinction from other modules**: Unlike standard CRUD Filament Resources (`PaymentResource`, `DebtResource`, `SubscriptionResource`, `CalendarResource`), the AI Assistant is a **real-time Livewire Page** (`App\Filament\Pages\AiAssistant`) backed by three dedicated Eloquent models (`AiAssistantSession`, `AiAssistantMessage`, `AiAssistantContext`) and an extensible tool registry (`AiToolRegistry`).

Perfect for:
- **Conversational financial inquiries** - Ask natural-language questions about expenses, incomes, transfers, and account balances in any language
- **Multi-session chat management** - Create, search, rename, switch, and clear conversation histories per user
- **Extensible AI tool calling** - Register new read/query tools via `AiAssistantToolInterface` without changing the chat loop
- **Usage & cost observability** - Track prompt/completion/total tokens, response latency (`latency_ms`), estimated `cost`, `model_used`, and full tool-call audit trails in the database

> **New Contributor Tip**: All orchestration lives in `app/Filament/Pages/AiAssistant.php`, while data-access capabilities exposed to the LLM live in `app/Services/AiAssistant/Tools/` and are registered in `AiToolRegistry`.

---

## 2. Architecture Overview

The AI Assistant module is organized into five main layers:

**1. Filament Page & Blade Views**:
- `app/Filament/Pages/AiAssistant.php` - Livewire page component (`/admin/ai-assistant`), session management, message dispatch, LLM HTTP client, and tool-calling loop
- `resources/views/filament/pages/ai-assistant.blade.php` - Two-column responsive page shell
- `resources/views/filament/pages/ai-assistant/history-sidebar.blade.php` - Session list, search, inline rename, delete, clear all
- `resources/views/filament/pages/ai-assistant/chat-window.blade.php` - Scrollable message list, auto-resize input, code-block copy buttons
- `resources/views/filament/pages/ai-assistant/message-bubble.blade.php` - User/assistant bubbles, Markdown rendering, animated thinking state
- `resources/views/filament/pages/ai-assistant/markdown-styles.blade.php` - Custom inline/scoped styles for rendered Markdown

**2. Models & Database Schema**:
- `app/Models/AiAssistantSession.php` (`ai_assistant_sessions`) - Chat threads per user with aggregated token counter
- `app/Models/AiAssistantMessage.php` (`ai_assistant_messages`) - Individual `user`, `assistant`, `tool`, and `system` messages with metrics & metadata
- `app/Models/AiAssistantContext.php` (`ai_assistant_contexts`) - Reusable system prompt personas and default model parameters

**3. Observers**:
- `app/Observers/AiAssistantSessionObserver.php`
- `app/Observers/AiAssistantMessageObserver.php`
- `app/Observers/AiAssistantContextObserver.php`

**4. AI Services & Tool Calling**:
- `app/Services/AiAssistant/AiToolRegistry.php` - Central registry & schema generator for LLM function calling
- `app/Services/AiAssistant/Tools/AiAssistantToolInterface.php` - Contract for all AI tools
- `app/Services/AiAssistant/Tools/GetPaymentTransactionsTool.php` - Queries `Payment` transactions with filters
- `app/Services/AiAssistant/Tools/GetPaymentBalancesTool.php` - Queries `PaymentAccount` balances and totals
- `app/Services/AiAssistant/AiPaymentContextService.php` - Static financial context builder *(currently unused/legacy; superseded by tool calling)*

**5. Configuration & Seeders**:
- `config/services.php` (`services.ai_assistant`) - Reads `CHATBOT_*` variables from `.env`
- `database/seeders/AiAssistantContextSeeder.php` - Seeds `Payments Assistant` and `General Assistant` contexts

### Key Relationships:
```
User
├── sessions (HasMany → AiAssistantSession)
└── messages (HasMany → AiAssistantMessage)

AiAssistantSession
├── user (BelongsTo → User)
└── messages (HasMany → AiAssistantMessage, FK: session_id)

AiAssistantMessage
├── session (BelongsTo → AiAssistantSession)
└── user (BelongsTo → User)

AiAssistantContext (Standalone active system-prompt configuration)
```

---

## 3. Current Development Status

This matrix summarizes what is currently built, what is partially wired, and what has not been built yet:

### 3.1 Implemented & Active
| Feature | Status | Details |
|---------|--------|---------|
| **Admin Chat Workspace** | Done | Accessible at `/admin/ai-assistant` under `Productivity` navigation group |
| **Multi-Session Management** | Done | Create, switch, live search, inline rename (`Enter`/`Escape`), delete single, clear all with confirmation |
| **Auto Session Titling** | Done | Automatically renames new/default sessions from the first user prompt (`Str::limit($trimmed, 30)`) |
| **Database Persistence** | Done | All sessions, user messages, assistant replies, and tool call request/result steps saved to SQL Server with `SoftDeletes` |
| **Activity Logging** | Done | All 3 models auto-assign `uuid` (`uuid7()`) & `user_id` and log CRUD lifecycle events via Observers |
| **OpenAI-Compatible Integration** | Done | Configurable `CHATBOT_API_URL`, `CHATBOT_API_KEY`, `CHATBOT_MODEL`, `CHATBOT_MAX_TOKENS`, `CHATBOT_TEMPERATURE` |
| **Multi-Iteration Tool Calling** | Done | Up to 5 tool-call iterations per prompt; removes `tools` before final iteration to guarantee a text answer |
| **Payment Data Tools** | Done | `get_payment_transactions` and `get_payment_balances` with IDR formatting via `toIndonesianCurrency()` |
| **Prompt & Response Tuning** | Done | Injects current date/time (`Asia/Jakarta`), enforces user-language matching, and isolates `reasoning`/`reasoning_content` from user-facing `content` |
| **Markdown & Code Copy UX** | Done | Renders Markdown via `Str::markdown()` with interactive copy buttons on `<pre>` code blocks and 25 rotating thinking phrases |
| **Usage Metrics & Error Logging** | Done | Stores `token_prompt`, `token_completion`, `token_total`, `latency_ms`, `cost`, `model_used`, `metadata`; logs HTTP/empty/exception errors via `Log::error` |

### 3.2 Partially Implemented (Backend Ready, UI Not Wired)
| Component | Location | Current State |
|-----------|----------|---------------|
| **Regenerate Last Response** | `AiAssistant::regenerateLastMessage()` | Method implemented (deletes last assistant message and re-dispatches `fetch-ai-response`), but no UI button is rendered in `message-bubble.blade.php` |
| **Quick Prompt Trigger** | `AiAssistant::sendPrompt()` | Method implemented, but no starter prompt chips/buttons in `chat-window.blade.php` |
| **Collapsible Sidebar** | `AiAssistant::toggleSidebar()` / `$isSidebarOpen` | Property & method exist, but layout does not bind `$isSidebarOpen` yet |
| **Model / Persona Switcher** | `$selectedModel`, `$systemPersona` | Properties exist (`$selectedModel` initialized from `config('services.ai_assistant.model')`), but no selector UI is rendered |
| **Reasoning Display** | `AiAssistantMessage::$reasoning_content` | Stored in DB and loaded into message array, but not rendered (e.g., optional collapsible "Thinking" accordion) |
| **Context Management CRUD** | `AiAssistantContext` model & seeder | Database table and seeder exist, but no Filament Resource exists to edit prompts/settings from `/admin` |
| **Static Context Service** | `AiPaymentContextService` | Implemented and imported in `AiAssistant.php`, but not invoked since tool calling replaced static prompt injection |

### 3.3 Not Yet Implemented
- Streaming responses (`wire:stream` / SSE) - currently waits for full HTTP completion (30s timeout per iteration)
- Tools for other modules (**Debts**, **Subscriptions**, **Calendar Events & Todos**, **Category Aggregations / Monthly Reports**)
- Dedicated REST API endpoints for external clients (e.g. NovaApp mobile integration)
- Per-message UI badges (model used, latency, token count, copy message button)
- File/image attachment uploads inside the chat window

---

## 4. Configuration

Environment variables in `.env` are mapped in `config/services.php` under `services.ai_assistant`:

```php
'ai_assistant' => [
  'api_url'     => env('CHATBOT_API_URL'),
  'api_key'     => env('CHATBOT_API_KEY'),
  'model'       => env('CHATBOT_MODEL', 'general-chat'),
  'max_tokens'  => (int) env('CHATBOT_MAX_TOKENS', 3072),
  'temperature' => (float) env('CHATBOT_TEMPERATURE', 0.3),
],
```

| Env Key | Default | Description |
|---------|---------|-------------|
| `CHATBOT_API_URL` | `null` | Base URL of the OpenAI-compatible API (`/chat/completions` is appended automatically) |
| `CHATBOT_API_KEY` | `null` | Bearer token for API authentication. If empty, the page returns the Service Unavailable fallback |
| `CHATBOT_MODEL` | `general-chat` | Model identifier sent in every chat completion request |
| `CHATBOT_MAX_TOKENS` | `3072` | Fallback max output tokens (overridden by active `AiAssistantContext::$max_tokens` if set) |
| `CHATBOT_TEMPERATURE` | `0.3` | Fallback sampling temperature (overridden by active `AiAssistantContext::$temperature` if set) |

---

## 5. Component Deep Dive

### 5.1 AiAssistantSession Model

```php
protected $fillable = [
  'uuid',               // UUIDv7 public identifier used in Livewire state
  'user_id',            // Owner user ID
  'title',              // Conversation title (default: 'New Conversation')
  'total_tokens_used',  // Cumulative token usage across messages
  'last_interacted_at', // Timestamp for ordering sidebar history
];
```

- **Scopes**: `forUser(int $userId)`
- **Relations**: `user()` (`BelongsTo`), `messages()` (`HasMany`)
- **Observer**: `AiAssistantSessionObserver` (`#[ObservedBy]`)

### 5.2 AiAssistantMessage Model

```php
protected $fillable = [
  'uuid',               // UUIDv7 identifier
  'session_id',         // Parent AiAssistantSession ID
  'user_id',            // Owner user ID
  'role',               // 'user' | 'assistant' | 'tool' | 'system'
  'content',            // Final message text (or JSON payload for role='tool')
  'reasoning_content',  // Optional internal reasoning from reasoning models
  'token_prompt',       // Prompt token count
  'token_completion',   // Completion token count
  'token_total',        // Total token count
  'latency_ms',         // Round-trip processing time in milliseconds
  'cost',               // Reported request cost (float)
  'model_used',         // Model returned by provider (or configured model)
  'status',             // 'processing' | 'completed'
  'error_message',      // Optional error string
  'metadata',           // JSON: context_name, injected_context, executed_tools, response_id, finish_reason, token details
];
```

- **Scopes**: `forSession(int $sessionId)`, `forUser(int $userId)`
- **Relations**: `session()` (`BelongsTo`), `user()` (`BelongsTo`)
- **UI Filtering**: When loading messages into the chat window, `role === 'tool'`, `role === 'system'`, and empty `assistant` messages (such as intermediate tool-call requests) are filtered out.

### 5.3 AiAssistantContext Model

```php
protected $fillable = [
  'uuid',
  'name',           // e.g. 'Payments Assistant', 'General Assistant'
  'description',
  'system_prompt',  // Base persona instructions
  'default_model',  // Default: 'general-chat'
  'temperature',    // Default: 0.30
  'max_tokens',     // Default: 3072
  'is_active',      // Default: false
];
```

- **Scope**: `active()` (`where('is_active', true)`)
- **Selection priority in `AiAssistant.php`**: Loads the active record named `'Payments Assistant'` first, falling back to the first active context, or `'You are a helpful AI assistant.'` if none exist.

### 5.4 AiAssistant Filament Page (`app/Filament/Pages/AiAssistant.php`)

| Method | Responsibility |
|--------|----------------|
| `mount()` | Initializes `$selectedModel` from config, loads user sessions ordered by `last_interacted_at` desc, creates an initial session if empty |
| `createNewSession()` | Creates a new `AiAssistantSession`, sets it active, clears current messages, sends Filament notification |
| `selectSession(string $sessionId)` | Switches active session and reloads filtered messages from DB |
| `editSession()` / `saveSessionTitle()` / `cancelEditSession()` | Inline rename workflow persisted to DB |
| `deleteSession(string $sessionId)` | Soft-deletes session messages and session, switches to next available session or creates a new one |
| `clearAllSessions()` | Soft-deletes all sessions and messages for the current user and creates a fresh session |
| `sendMessage(?string $message)` | Saves user message, appends `Thinking...` placeholder, updates session title & timestamp, dispatches `fetch-ai-response` |
| `processAiResponse()` | Listener for `#[On('fetch-ai-response')]`; invokes `fetchAiResponse()`, saves assistant message + metrics, increments session `total_tokens_used`, updates UI |
| `fetchAiResponse(string $prompt)` | Builds system prompt + last 10 messages, executes multi-iteration LLM + tool-calling loop, isolates reasoning, logs errors, returns structured array or fallback string |
| `regenerateLastMessage()` | Removes last assistant message from DB & state and re-dispatches `fetch-ai-response` *(not yet bound in UI)* |

### 5.5 Registered AI Tools

All tools implement `App\Services\AiAssistant\Tools\AiAssistantToolInterface`:
- `getName(): string`
- `getDescription(): string`
- `getParametersSchema(): array`
- `execute(array $arguments, ?int $userId = null): mixed`

#### 1. `get_payment_transactions` (`GetPaymentTransactionsTool`)
Queries `Payment` records scoped to the authenticated user.

| Parameter | Type | Description |
|-----------|------|-------------|
| `month` | `integer` | Filter by month (`1`–`12`) |
| `year` | `integer` | Filter by year (e.g. `2026`) |
| `type` | `string` | `expense` (`type_id = 1`), `income` (`type_id = 2`), or `transfer` (`type_id` in `[3, 4]`) |
| `search` | `string` | `LIKE` match on `name` or `code` |
| `sort` | `string` | `asc` (oldest) or `desc` (newest, default) |
| `limit` | `integer` | Max records returned (default `5`, capped at `20`) |

**Returns**: `count` and `transactions[]` (`id`, `code`, `date`, `name`, `type`, `category`, `account`, `amount`, `formatted_amount`, `is_draft`, `is_scheduled`).

#### 2. `get_payment_balances` (`GetPaymentBalancesTool`)
Queries all `PaymentAccount` records scoped to the authenticated user (no parameters).

**Returns**: `total_balance`, `formatted_total_balance`, and `accounts[]` (`id`, `name`, `deposit`, `formatted_deposit`).

---

## 6. Data Flow Examples

### 6.1 End-to-End Prompt with Tool Calling

**Scenario**: User asks `"Are there any transactions in November 2026?"`

1. **UI Submit**: `chat-window.blade.php` calls `$wire.sendMessage(text)`.
2. **Persist User Turn**: `sendMessage()` creates an `AiAssistantMessage` (`role = 'user'`), appends a `Thinking...` placeholder to `$this->messages`, updates the session title if needed, and dispatches `fetch-ai-response`.
3. **Prepare Prompt**: `processAiResponse()` triggers `fetchAiResponse()`, which builds:
   - System message: `Current Date & Time` + active `AiAssistantContext::$system_prompt` + Response Guidelines (language matching, no internal reasoning leakage, concise verified answers).
   - Last 10 messages from `$this->messages`.
   - `tools` schema from `AiToolRegistry::getToolsSchema()`.
4. **Tool Loop (up to 5 iterations)**:
   - Iteration 0: LLM responds with `tool_calls: [{ name: "get_payment_transactions", arguments: { month: 11, year: 2026 } }]`.
   - `AiAssistant` logs the tool request message (`role = 'assistant'`, `metadata.step = 'tool_call_request'`), executes `GetPaymentTransactionsTool`, logs the result (`role = 'tool'`, `metadata.step = 'tool_call_result'`), and appends both to `$formattedMessages`.
   - Iteration 1: LLM receives the tool result and either calls another tool (e.g. broader query) or produces the final text response in the user's language.
   - Before the final allowed iteration (`$iteration === $maxToolIterations - 2`), `tools` is unset from `$payload` so the model must return a final text answer.
5. **Persist Assistant Turn**: `processAiResponse()` stores the final `AiAssistantMessage` (`content`, `reasoning_content`, `token_*`, `latency_ms`, `cost`, `model_used`, `metadata`), backfills `model_used` on the user message, increments `AiAssistantSession.total_tokens_used`, and replaces the `Thinking...` placeholder in the UI.

### 6.2 Fallback Behavior on API Failure

If `CHATBOT_API_KEY` or `CHATBOT_API_URL` is missing, the provider returns a non-2xx status, an exception occurs, or the model returns empty `content`:
1. The failure details (`status`, `body`, `model`, `iteration`, or exception message) are written to `storage/logs/laravel.log` via `Log::error`.
2. `fetchAiResponse()` returns `generateLocalFallbackResponse()`, displaying the `AI Assistant Service Unavailable` notice without leaking raw exceptions or internal reasoning.

---

## 7. Key Patterns & Extending the Module

### 7.1 Adding a New AI Tool

1. Create a class in `app/Services/AiAssistant/Tools/` implementing `AiAssistantToolInterface`:
   ```php
   class GetActiveSubscriptionsTool implements AiAssistantToolInterface
   {
     public function getName(): string
     {
       return 'get_active_subscriptions';
     }

     public function getDescription(): string
     {
       return 'Retrieve active recurring subscriptions and next billing dates for the user.';
     }

     public function getParametersSchema(): array
     {
       return [
         'type'       => 'object',
         'properties' => [],
         'required'   => [],
       ];
     }

     public function execute(array $arguments, ?int $userId = null): mixed
     {
       // Query scoped to getUser($userId)?->id
     }
   }
   ```
2. Register it in `AiToolRegistry::__construct()`:
   ```php
   $this->register(new GetActiveSubscriptionsTool());
   ```
3. No changes to `AiAssistant.php` are required - `getToolsSchema()` and `executeTool()` pick it up automatically.

### 7.2 Inline CSS Convention for Custom Filament Views

Per project standards, custom Filament Blade views under `resources/views/filament/pages/ai-assistant/` use native inline CSS (`style="..."`) for layout/flex/spacing alongside standard `<x-filament::...>` components, avoiding uncompiled custom Tailwind utilities.

---

## 8. Recommended Improvements & Roadmap

1. **Expand Tool Ecosystem**: Add tools for `Debt` / `DebtInstallment`, `Subscription`, `CalendarEvent` / `CalendarTodo`, and a `get_payment_summary` aggregation tool (monthly/category totals without hitting the 20-record transaction limit).
2. **Manage Contexts in Admin Panel**: Create an `AiAssistantContextResource` so system prompts, `temperature`, `max_tokens`, and active status can be edited via Filament UI instead of seeders/DB edits.
3. **Wire Existing Backend Actions to UI**: Expose buttons for `regenerateLastMessage()`, copy response, starter prompt chips (`sendPrompt()`), and optional session persona/context selection.
4. **Streaming Responses**: Adopt Livewire `wire:stream` with SSE from the LLM endpoint to eliminate waiting on full 30-second responses.
5. **Cleanup Legacy Code**: Remove or repurpose the unused `AiPaymentContextService` import/class if static prompt injection is no longer needed.
6. **Observability in UI**: Optionally display a subtle metadata footer per assistant message (model used, latency, token count) and a collapsible reasoning toggle when `reasoning_content` is present.

---

## 9. Troubleshooting

### 9.1 "AI Assistant Service Unavailable" Response
- **Cause**: Missing `CHATBOT_API_URL` / `CHATBOT_API_KEY`, invalid model name in `CHATBOT_MODEL`, provider 4xx/5xx error, timeout (>30s), or empty `content` returned by the model.
- **Solution**: Check `storage/logs/laravel.log` for `AI Assistant request failed`, `AI Assistant empty content response`, or `AI Assistant exception`. Run `php artisan config:clear` if `.env` was recently updated.

### 9.2 Model Responds in the Wrong Language or Leaks Internal Thoughts
- **Cause**: Custom `AiAssistantContext` prompt conflicts with language rules, or provider returns chain-of-thought inside `content` instead of `reasoning`/`reasoning_content`.
- **Solution**: Ensure `AiAssistant.php` keeps the `Response Guidelines` block appended to `$systemPrompt` and never falls back from empty `content` to `reasoning_content`.

### 9.3 Tool Calls Return Empty Data
- **Cause**: `getUser($userId)` resolves to a different user, or the user has no matching `Payment` / `PaymentAccount` records for the requested date filter.
- **Solution**: Inspect the `ai_assistant_messages` table rows with `role = 'tool'` and `metadata->step = 'tool_call_result'` to verify the exact arguments and returned payload.

### 9.4 Session History Not Appearing
- **Cause**: `ai_assistant_sessions` or `ai_assistant_messages` migrations not run, or records were soft-deleted.
- **Solution**: Verify migrations are applied (`php artisan migrate`) and check `deleted_at` values in `ai_assistant_sessions`.

---

*Copyright © 2026 Nova Ardiansyah*  
*Website: [https://novaardiansyah.id](https://novaardiansyah.id)*  
*Email: [admin@novaardiansyah.id](mailto:admin@novaardiansyah.id)*  
*Phone: [0822 6111 1084](https://wa.me/6282261111084)*
