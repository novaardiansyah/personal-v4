<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('backup_schedules', function (Blueprint $table) {
      $table->unsignedBigInteger('sum_file_size')->default(0)->after('max_count_backup');
    });
  }

  public function down(): void
  {
    Schema::table('backup_schedules', function (Blueprint $table) {
      $table->dropColumn('sum_file_size');
    });
  }
};
