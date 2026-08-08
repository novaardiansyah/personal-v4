<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('backup_schedules', function (Blueprint $table) {
      $table->dropColumn('retention_days');
      $table->unsignedInteger('count_backup')->default(0)->after('filename_pattern');
      $table->unsignedInteger('max_count_backup')->default(5)->after('count_backup');
    });
  }

  public function down(): void
  {
    Schema::table('backup_schedules', function (Blueprint $table) {
      $table->dropColumn(['count_backup', 'max_count_backup']);
      $table->unsignedInteger('retention_days')->default(3)->after('filename_pattern');
    });
  }
};
