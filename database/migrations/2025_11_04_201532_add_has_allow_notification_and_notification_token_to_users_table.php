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
    Schema::table('users', function (Blueprint $table) {
      $table->boolean('has_allow_notification')->default(false)->after('remember_token');
      $table->string('notification_token')->nullable()->after('has_allow_notification');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('users', function (Blueprint $table) {
      $table->dropColumn('has_allow_notification');
      $table->dropColumn('notification_token');
    });
  }
};
