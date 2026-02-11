<?php

/*
 * Project Name: personal-v4
 * File: 2026_02_11_103728_add_status_to_uptime_monitors_table.php
 * Created Date: Wednesday February 11th 2026
 *
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 *
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

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
    Schema::table('uptime_monitors', function (Blueprint $table) {
      $table->string('status')->nullable();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('uptime_monitors', function (Blueprint $table) {
      $table->dropColumn('status');
    });
  }
};
