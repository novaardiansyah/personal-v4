<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('ai_assistant_contexts', function (Blueprint $table) {
      $table->json('allowed_tools')->nullable()->after('is_active');
      $table->json('starter_prompts')->nullable()->after('allowed_tools');
      $table->integer('sort_order')->default(0)->after('starter_prompts');
    });

    Schema::table('ai_assistant_sessions', function (Blueprint $table) {
      $table->foreignId('context_id')->nullable()->after('user_id')->constrained('ai_assistant_contexts')->noActionOnDelete();
      $table->string('model_override')->nullable()->after('title');
      $table->boolean('is_pinned')->default(false)->after('model_override');
      $table->decimal('total_cost', 12, 6)->default(0)->after('total_tokens_used');
      $table->text('summary')->nullable()->after('total_cost');
    });

    Schema::table('ai_assistant_messages', function (Blueprint $table) {
      $table->index(['session_id', 'created_at'], 'ai_messages_session_created_idx');
      $table->index(['user_id', 'created_at'], 'ai_messages_user_created_idx');
    });
  }

  public function down(): void
  {
    Schema::table('ai_assistant_messages', function (Blueprint $table) {
      $table->dropIndex('ai_messages_session_created_idx');
      $table->dropIndex('ai_messages_user_created_idx');
    });

    Schema::table('ai_assistant_sessions', function (Blueprint $table) {
      $table->dropConstrainedForeignId('context_id');
      $table->dropColumn([
        'model_override',
        'is_pinned',
        'total_cost',
        'summary',
      ]);
    });

    Schema::table('ai_assistant_contexts', function (Blueprint $table) {
      $table->dropColumn([
        'allowed_tools',
        'starter_prompts',
        'sort_order',
      ]);
    });
  }
};
