<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		Schema::create('ai_assistant_messages', function (Blueprint $table) {
			$table->id();
			$table->uuid('uuid')->unique();
			$table->foreignId('session_id')->constrained('ai_assistant_sessions')->noActionOnDelete();
			$table->foreignId('user_id')->constrained('users')->noActionOnDelete();
			$table->enum('role', ['user', 'assistant', 'system', 'tool'])->default('user');
			$table->text('content')->nullable();
			$table->text('reasoning_content')->nullable();
			$table->bigInteger('token_prompt')->default(0);
			$table->bigInteger('token_completion')->default(0);
			$table->bigInteger('token_total')->default(0);
			$table->bigInteger('latency_ms')->default(0);
			$table->string('model_used')->nullable();
			$table->enum('status', ['completed', 'failed', 'processing'])->default('processing');
			$table->text('error_message')->nullable();
			$table->json('metadata')->nullable();
			$table->softDeletes();
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('ai_assistant_messages');
	}
};
