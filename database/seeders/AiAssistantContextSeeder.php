<?php

namespace Database\Seeders;

use App\Models\AiAssistantContext;
use Illuminate\Database\Seeder;

class AiAssistantContextSeeder extends Seeder
{
  public function run(): void
  {
    AiAssistantContext::updateOrCreate(
      ['name' => 'Payments Assistant'],
      [
        'uuid'            => uuid7(),
        'description'     => 'Smart financial assistant for analyzing payment transactions, expenses, incomes, balances, goals, debts, and subscriptions.',
        'system_prompt'   => 'You are an intelligent Financial & Payment Assistant. Your goal is to help users analyze expenses, incomes, account balances, savings goals, debts, and recurring subscriptions accurately and professionally. Always format monetary values using Indonesian Rupiah (Rp).',
        'default_model'   => 'general-chat',
        'temperature'     => 0.30,
        'max_tokens'      => 3072,
        'is_active'       => true,
        'sort_order'      => 1,
        'allowed_tools'   => [
          'get_payment_transactions',
          'get_payment_balances',
          'get_payment_summary',
          'get_payment_goals',
          'get_debts_overview',
          'get_subscriptions',
          'get_payment_items',
          'create_draft_payment',
          'generate_financial_report_pdf',
        ],
        'starter_prompts' => [
          'Summarize my income and expenses this month',
          'What are my current payment account balances?',
          'Show my upcoming subscription renewals',
          'Check my active debts and savings goals',
        ],
      ]
    );

    AiAssistantContext::updateOrCreate(
      ['name' => 'Productivity & Planner'],
      [
        'uuid'            => uuid7(),
        'description'     => 'Personal productivity assistant for managing calendar schedules, todos, notes, and files.',
        'system_prompt'   => 'You are a focused Personal Productivity & Planning Assistant. Help users organize their calendar events, prioritize todos, search personal notes, and locate files efficiently.',
        'default_model'   => 'general-chat',
        'temperature'     => 0.30,
        'max_tokens'      => 3072,
        'is_active'       => true,
        'sort_order'      => 2,
        'allowed_tools'   => [
          'get_calendar_schedule',
          'get_calendar_todos',
          'search_notes',
          'search_files',
          'create_calendar_todo',
          'create_calendar_event',
          'create_note',
        ],
        'starter_prompts' => [
          'What is on my schedule this week?',
          'List my pending and overdue todos',
          'Search my notes for project ideas',
        ],
      ]
    );

    AiAssistantContext::updateOrCreate(
      ['name' => 'System & DevOps Monitor'],
      [
        'uuid'            => uuid7(),
        'description'     => 'Operational assistant for monitoring uptime health, backup schedules, communications, and activity logs.',
        'system_prompt'   => 'You are a concise System & DevOps Monitoring Assistant. Help users inspect uptime monitor status, backup runs, recent emails/contact messages, blog metrics, and activity logs.',
        'default_model'   => 'general-chat',
        'temperature'     => 0.20,
        'max_tokens'      => 3072,
        'is_active'       => true,
        'sort_order'      => 3,
        'allowed_tools'   => [
          'get_uptime_status',
          'get_backup_status',
          'get_recent_communications',
          'get_blog_insights',
          'get_activity_logs',
        ],
        'starter_prompts' => [
          'Check current uptime monitor status',
          'Show latest backup runs and any failures',
          'Summarize recent contact messages and emails',
        ],
      ]
    );

    AiAssistantContext::updateOrCreate(
      ['name' => 'General Assistant'],
      [
        'uuid'            => uuid7(),
        'description'     => 'General-purpose assistant with access to all personal-v4 modules and tools.',
        'system_prompt'   => 'You are a friendly, professional, and versatile AI Assistant for personal-v4. Help users across finances, productivity, notes, and system monitoring.',
        'default_model'   => 'general-chat',
        'temperature'     => 0.30,
        'max_tokens'      => 3072,
        'is_active'       => true,
        'sort_order'      => 4,
        'allowed_tools'   => null,
        'starter_prompts' => [
          'Give me a full overview of my finances and schedule today',
          'What are my account balances and pending todos?',
          'Check system uptime and latest backups',
        ],
      ]
    );
  }
}
