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
    Schema::dropIfExists('uptime_logs');
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::create('uptime_logs', function (Blueprint $table) {
      $table->id();
      $table->string('url');
      $table->enum('status', ['up', 'down'])->default('down');
      $table->unsignedInteger('response_time')->default(0);
      $table->text('error')->nullable();
      $table->timestamp('checked_at', 0);
      $table->timestamps();
    });
  }
};
