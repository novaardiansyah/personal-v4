# AI Assistant Module Documentation

*Version: v2.0*  
*Last Updated: November 28, 2026*

## 1. Introduction

The **AI Assistant** module (`/admin/ai-assistant`) is an interactive, database-backed conversational AI workspace built as a custom Filament v5 Page inside the admin panel, powered by a shared service layer (`AiAssistantService` and `AiLlmClient`) that also serves Sanctum REST API endpoints and scheduled Telegram briefings.

It connects to any OpenAI-compatible Chat Completions endpoint (including SSE streaming) and supports **multi-step tool calling** across 21 registered tools covering Finance, Productivity, Infrastructure/Monitoring, and guarded human-in-the-loop write actions.

---

## 2. Architecture Overview

1. **Filament Page & Blade Views**:
   - `app/Filament/Pages/AiAssistant.php` — Livewire page component (`/admin/ai-assistant`) with lazy message pagination, chronological session grouping, pinning, persona/model switching, SSE streaming (`wire:stream`), image attachment uploads, Markdown/PDF export, and write-action confirmation.
   - `resources/views/filament/pages/ai-assistant.blade.php` — Responsive shell with collapsible sidebar.
   - `resources/views/filament/pages/ai-assistant/history-sidebar.blade.php` — Chronologically grouped & pinned session list, search, inline rename, pin, delete, clear all.
   - `resources/views/filament/pages/ai-assistant/chat-window.blade.php` — Persona/model header controls, export actions, starter prompt chips, image attachment input, and chat stream.
   - `resources/views/filament/pages/ai-assistant/message-bubble.blade.php` — Markdown rendering, collapsible reasoning accordion, tool badges, pending write-action confirmation cards, metadata footer (model, latency, tokens), copy, and regenerate buttons.

2. **Admin Governance & Analytics**:
   - `app/Filament/Resources/AiAssistantContexts/AiAssistantContextResource.php` — Full CRUD resource (`Schemas/`, `Tables/`, `Pages/`) to manage AI personas, system prompts, model parameters, `allowed_tools`, and `starter_prompts`.
   - `app/Filament/Resources/AiAssistantContexts/Widgets/AiAssistantStatsWidget.php` — 30-day session, token usage, estimated cost, and average latency stats widget.

3. **Core AI Services**:
   - `app/Services/AiAssistant/AiLlmClient.php` — Non-streaming HTTP and SSE streaming cURL client for OpenAI-compatible `/chat/completions`.
   - `app/Services/AiAssistant/AiAssistantService.php` — Context resolution, DB-backed conversation history, rate limiting (`ai_assistant_rate_limit_per_minute`, `ai_assistant_daily_token_limit`), cumulative multi-iteration token/cost tracking, guarded write confirmation, and Markdown/PDF export.
   - `app/Services/AiAssistant/AiToolRegistry.php` — Central tool registry with per-context `allowed_tools` scoping.

4. **Registered AI Tools (`app/Services/AiAssistant/Tools/`)**:
   - **Financial**: `get_payment_transactions`, `get_payment_balances`, `get_payment_summary`, `get_payment_goals`, `get_debts_overview`, `get_subscriptions`, `get_payment_items`
   - **Productivity**: `get_calendar_schedule`, `get_calendar_todos`, `search_notes`, `search_files`
   - **Infrastructure & Operations**: `get_uptime_status`, `get_backup_status`, `get_recent_communications`, `get_blog_insights`, `get_activity_logs`
   - **Guarded Write & Reports**: `create_draft_payment`, `create_calendar_todo`, `create_calendar_event`, `create_note`, `generate_financial_report_pdf`

5. **REST API & Background Briefings**:
   - `app/Http/Controllers/Api/AiAssistantController.php` & `routes/api/ai-assistant.php` — Sanctum-authenticated endpoints under `/api/ai-assistant/*`.
   - `app/Jobs/AiAssistant/SendDailyAiBriefingJob.php` — Queued job that generates a daily financial, schedule, and infrastructure briefing and dispatches it via `sendTelegramNotification()`.

---

## 3. Database Schema

- `ai_assistant_contexts`: `uuid`, `name`, `description`, `system_prompt`, `default_model`, `temperature`, `max_tokens`, `is_active`, `allowed_tools` (JSON), `starter_prompts` (JSON), `sort_order`.
- `ai_assistant_sessions`: `user_id`, `context_id`, `uuid`, `title`, `model_override`, `is_pinned`, `total_tokens_used`, `total_cost`, `summary`, `last_interacted_at`.
- `ai_assistant_messages`: `uuid`, `session_id`, `user_id`, `role`, `content`, `reasoning_content`, `token_prompt`, `token_completion`, `token_total`, `latency_ms`, `cost`, `model_used`, `status` (`processing` | `completed` | `failed`), `error_message`, `metadata`, with composite indexes `(session_id, created_at)` and `(user_id, created_at)`.
