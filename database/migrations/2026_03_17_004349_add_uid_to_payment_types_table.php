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
		Schema::table('payment_types', function (Blueprint $table) {
			$table->string('uid', 36)->nullable()->after('id');
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::table('payment_types', function (Blueprint $table) {
			$table->dropColumn('uid');
		});
	}
};
