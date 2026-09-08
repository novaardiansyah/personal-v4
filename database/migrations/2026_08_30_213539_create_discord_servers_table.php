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
		Schema::create('discord_servers', function (Blueprint $table) {
			$table->id();
			$table->string('name');
			$table->string('server_id')->nullable();
			$table->string('server_icon')->nullable();
			$table->text('description')->nullable();
			$table->boolean('is_active')->default(true);
			$table->softDeletes();
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('discord_servers');
	}
};
