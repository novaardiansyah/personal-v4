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
		Schema::table('files', function (Blueprint $table) {
			$table->string('encrypt_key')->after('uid')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::table('files', function (Blueprint $table) {
			$table->dropColumn('encrypt_key');
		});
	}
};
