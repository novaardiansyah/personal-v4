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
		Schema::table('backups', function (Blueprint $table) {
			$table->foreignId('backup_job_id')->nullable()->constrained('backup_jobs')->noActionOnDelete();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::table('backups', function (Blueprint $table) {
			$table->dropForeign(['backup_job_id']);
			$table->dropColumn('backup_job_id');
		});
	}
};
