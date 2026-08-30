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
		Schema::create('discord_webhooks', function (Blueprint $table) {
			$table->id();
			$table->uuid('uid')->nullable()->unique();
			$table->foreignId('channel_id')->constrained('discord_channels')->noActionOnDelete();
			$table->string('name');
			$table->text('url');
			$table->text('description')->nullable();
			$table->softDeletes();
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('discord_webhooks');
	}
};
