<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		Schema::create('backups', function (Blueprint $table) {
			$table->id();
			$table->string('uid')->unique();
			$table->string('file_name')->nullable();
			$table->string('file_path')->nullable();
			$table->string('file_size')->nullable();
			$table->string('checksum', 64)->nullable();
			$table->enum('type', ['full', 'database', 'files', 'incremental'])->nullable();
			$table->timestamp('started_at')->nullable();
			$table->timestamp('completed_at')->nullable();
			$table->unsignedInteger('duration')->default(0);
			$table->enum('status', ['pending', 'success', 'failed'])->default('pending');
			$table->text('message')->nullable();
			$table->string('server_name')->nullable();
			$table->softDeletes();
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('backups');
	}
};
