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
    Schema::table('backup_schedules', function (Blueprint $table) {
      if (Schema::hasColumn('backup_schedules', 'destination_path') && !Schema::hasColumn('backup_schedules', 'local_destination_path')) {
        $table->renameColumn('destination_path', 'local_destination_path');
      }
      if (!Schema::hasColumn('backup_schedules', 'r2_destination_path')) {
        $table->text('r2_destination_path')->nullable()->after('local_destination_path');
      }
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('backup_schedules', function (Blueprint $table) {
      if (Schema::hasColumn('backup_schedules', 'r2_destination_path')) {
        $table->dropColumn('r2_destination_path');
      }
      if (Schema::hasColumn('backup_schedules', 'local_destination_path') && !Schema::hasColumn('backup_schedules', 'destination_path')) {
        $table->renameColumn('local_destination_path', 'destination_path');
      }
    });
  }
};
