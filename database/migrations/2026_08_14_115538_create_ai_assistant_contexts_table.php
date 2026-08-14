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
		Schema::create('ai_assistant_contexts', function (Blueprint $table) {
			$table->id();
			$table->uuid('uuid')->unique();
			$table->string('name');
			$table->text('description')->nullable();
			$table->text('system_prompt')->nullable();
			$table->string('default_model')->default('general-chat');
			$table->decimal('temperature', 25, 5)->default(0.30);
			$table->decimal('max_tokens', 25, 5)->default(3072);
			$table->boolean('is_active')->default(false);
			$table->softDeletes();
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('ai_assistant_contexts');
	}
};
