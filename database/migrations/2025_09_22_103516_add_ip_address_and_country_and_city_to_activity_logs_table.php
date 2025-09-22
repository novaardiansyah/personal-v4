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
    Schema::table('activity_logs', function (Blueprint $table) {
      $table->string('ip_address')->nullable()->after('batch_uuid');
      $table->string('country')->nullable()->after('ip_address');
      $table->string('city')->nullable()->after('country');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('activity_logs', function (Blueprint $table) {
      $table->dropColumn(['ip_address', 'country', 'city']);
    });
  }
};
