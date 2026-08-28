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
		Schema::table('backup_storages', function (Blueprint $table) {
			$table->foreignId('provider_id')->nullable()->after('id')->constrained('backup_storage_providers')->noActionOnDelete();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::table('backup_storages', function (Blueprint $table) {
			$table->dropForeign(['provider_id']);
			$table->dropColumn('provider_id');
		});
	}
};
