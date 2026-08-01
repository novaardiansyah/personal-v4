<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('backup_schedules', function (Blueprint $table) {
      if (!Schema::hasColumn('backup_schedules', 'is_sync_cloud')) {
        $table->boolean('is_sync_cloud')->default(false)->after('local_destination_path');
      }
    });

    DB::table('backup_schedules')
      ->whereNotNull('r2_destination_path')
      ->where('r2_destination_path', '!=', '')
      ->update(['is_sync_cloud' => true]);
  }

  public function down(): void
  {
    Schema::table('backup_schedules', function (Blueprint $table) {
      if (Schema::hasColumn('backup_schedules', 'is_sync_cloud')) {
        $table->dropColumn('is_sync_cloud');
      }
    });
  }
};
