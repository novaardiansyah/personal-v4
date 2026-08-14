<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::table('ai_assistant_messages', function (Blueprint $table) {
			$table->decimal('cost', 14, 6)->default(0)->after('latency_ms');
		});
	}

	public function down(): void
	{
		Schema::table('ai_assistant_messages', function (Blueprint $table) {
			$table->dropColumn('cost');
		});
	}
};
