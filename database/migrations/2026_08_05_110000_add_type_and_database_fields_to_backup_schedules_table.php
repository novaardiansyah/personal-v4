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
      if (Schema::hasColumn('backup_schedules', 'source_path')) {
        $table->text('source_path')->nullable()->change();
      }
      if (! Schema::hasColumn('backup_schedules', 'type')) {
        $table->string('type')->default('files')->after('uid');
      }
      if (! Schema::hasColumn('backup_schedules', 'drivers')) {
        $table->string('drivers')->nullable()->after('type');
      }
      if (! Schema::hasColumn('backup_schedules', 'database_name')) {
        $table->string('database_name')->nullable()->after('drivers');
      }
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('backup_schedules', function (Blueprint $table) {
      if (Schema::hasColumn('backup_schedules', 'database_name')) {
        $table->dropColumn('database_name');
      }
      if (Schema::hasColumn('backup_schedules', 'drivers')) {
        $table->dropColumn('drivers');
      }
      if (Schema::hasColumn('backup_schedules', 'type')) {
        $table->dropColumn('type');
      }
    });
  }
};
