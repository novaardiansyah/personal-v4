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
    Schema::create('uptime_monitors', function (Blueprint $table) {
      $table->id();
      $table->string('code')->unique();
      $table->string('url');
      $table->string('name')->nullable();
      $table->unsignedInteger('interval')->default(5);
      $table->boolean('is_active')->default(true);
      $table->timestamp('last_checked_at')->nullable();
      $table->timestamp('last_healthy_at')->nullable();
      $table->timestamp('last_unhealthy_at')->nullable();
      $table->integer('total_checks')->default(0);
      $table->integer('healthy_checks')->default(0);
      $table->integer('unhealthy_checks')->default(0);
      $table->softDeletes();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('uptime_monitors');
  }
};
