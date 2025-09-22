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
      $table->string('timezone')->nullable()->after('geolocation');
      $table->string('user_agent')->nullable()->after('timezone');
      $table->string('referer')->nullable()->after('user_agent');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('activity_logs', function (Blueprint $table) {
      $table->dropColumn(['timezone', 'user_agent', 'referer']);
    });
  }
};
