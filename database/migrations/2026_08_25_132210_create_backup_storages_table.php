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
		Schema::create('backup_storages', function (Blueprint $table) {
			$table->id();
			$table->uuid('uid')->unique();
			$table->string('name')->nullable();
			$table->string('slug')->nullable();
			$table->json('keys')->nullable();
			$table->boolean('active')->default(false);
			$table->softDeletes();
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('backup_storages');
	}
};
