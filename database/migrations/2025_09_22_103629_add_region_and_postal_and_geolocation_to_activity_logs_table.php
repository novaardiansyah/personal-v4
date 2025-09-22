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
      $table->string('region')->nullable()->after('city');
      $table->string('postal')->nullable()->after('region');
      $table->string('geolocation')->nullable()->after('postal');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('activity_logs', function (Blueprint $table) {
      $table->dropColumn(['region', 'postal', 'geolocation']);
    });
  }
};
