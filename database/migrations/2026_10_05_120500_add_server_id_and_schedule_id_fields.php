<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('backup_schedules', function (Blueprint $table) {
      $table->foreignId('server_id')->nullable()->after('storage_id')->constrained('backup_storages')->noActionOnDelete();
    });

    Schema::table('backups', function (Blueprint $table) {
      $table->foreignId('server_id')->nullable()->after('storage_id')->constrained('backup_storages')->noActionOnDelete();
      $table->foreignId('schedule_id')->nullable()->after('server_id')->constrained('backup_schedules')->noActionOnDelete();
    });
  }

  public function down(): void
  {
    Schema::table('backups', function (Blueprint $table) {
      $table->dropForeign(['schedule_id']);
      $table->dropColumn('schedule_id');
      $table->dropForeign(['server_id']);
      $table->dropColumn('server_id');
    });

    Schema::table('backup_schedules', function (Blueprint $table) {
      $table->dropForeign(['server_id']);
      $table->dropColumn('server_id');
    });
  }
};
