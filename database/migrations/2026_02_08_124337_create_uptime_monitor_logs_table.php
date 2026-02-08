<?php

/*
 * Project Name: personal-v4
 * File: 2026_02_08_124337_create_uptime_monitor_logs_table.php
 * Created Date: Sunday February 8th 2026
 *
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 *
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

declare(strict_types=1);

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
    Schema::create('uptime_monitor_logs', function (Blueprint $table) {
      $table->id();
      $table->foreignId('uptime_monitor_id')->constrained('uptime_monitors')->cascadeOnDelete();
      $table->integer('status_code')->nullable();
      $table->unsignedInteger('response_time_ms')->default(0);
      $table->boolean('is_healthy')->default(false);
      $table->text('error_message')->nullable();
      $table->timestamp('checked_at')->nullable();
      $table->softDeletes();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('uptime_monitor_logs');
  }
};
