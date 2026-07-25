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
		Schema::create('backup_jobs', function (Blueprint $table) {
			$table->id();
			$table->foreignId('backup_schedule_id')->constrained('backup_schedules')->noActionOnDelete();
			$table->enum('status', ['pending', 'running', 'success', 'failed'])->default('pending');
			$table->timestamp('assigned_at')->nullable();
			$table->timestamp('started_at')->nullable();
			$table->timestamp('finished_at')->nullable();
			$table->text('message')->nullable();
			$table->softDeletes();
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('backup_jobs');
	}
};
