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
		Schema::create('ai_assistant_sessions', function (Blueprint $table) {
			$table->id();
			$table->foreignId('user_id')->constrained('users')->noActionOnDelete();
			$table->uuid('uuid')->unique();
			$table->string('title')->default('New Conversation');
			$table->bigInteger('total_tokens_used')->default(0);
			$table->timestamp('last_interacted_at')->nullable();
			$table->softDeletes();
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('ai_assistant_sessions');
	}
};
