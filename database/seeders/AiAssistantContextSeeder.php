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
        'uuid'          => uuid7(),
        'description'   => 'Smart financial assistant for analyzing payment transactions, expenses, incomes, and account balances.',
        'system_prompt' => 'You are an intelligent Financial & Payment Assistant. Your goal is to help users analyze expenses, incomes, account balances, and transaction history accurately and professionally. Always format monetary values using Indonesian Rupiah (Rp).',
        'default_model' => 'general-chat',
        'temperature'   => 0.30,
        'max_tokens'    => 3072,
        'is_active'     => true,
      ]
    );

    AiAssistantContext::updateOrCreate(
      ['name' => 'General Assistant'],
      [
        'uuid'          => uuid7(),
        'description'   => 'General-purpose assistant for daily productivity tasks and inquiries.',
        'system_prompt' => 'You are a friendly, professional, and versatile AI Assistant. Help users complete their daily tasks and answer questions productively.',
        'default_model' => 'general-chat',
        'temperature'   => 0.30,
        'max_tokens'    => 3072,
        'is_active'     => true,
      ]
    );
  }
}
