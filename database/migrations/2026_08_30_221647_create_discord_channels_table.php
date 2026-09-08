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
		Schema::create('discord_channels', function (Blueprint $table) {
			$table->id();
			$table->foreignId('server_id')->constrained('discord_servers')->noActionOnDelete();
			$table->string('name');
			$table->string('channel_id')->nullable();
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
		Schema::dropIfExists('discord_channels');
	}
};
