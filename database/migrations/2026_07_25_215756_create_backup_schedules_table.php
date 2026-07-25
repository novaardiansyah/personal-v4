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
		Schema::create('backup_schedules', function (Blueprint $table) {
			$table->id();
			$table->string('uid')->unique();
			$table->text('source_path');
			$table->text('destination_path')->default('backups');
			$table->string('filename_pattern')->default('backup-{Ymd-His}');
			$table->unsignedInteger('retention_days')->default(3);
			$table->unsignedInteger('interval_value')->default(3);
			$table->enum('interval_unit', ['minutes', 'hours', 'days', 'weeks', 'months'])->default('days');
			$table->timestamp('next_backup_at')->nullable();
			$table->timestamp('last_backup_at')->nullable();
			$table->boolean('is_enabled')->default(true);
			$table->softDeletes();
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('backup_schedules');
	}
};
