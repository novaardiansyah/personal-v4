<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('backup_schedules', function (Blueprint $table) {
      if (!Schema::hasColumn('backup_schedules', 'include')) {
        $table->json('include')->nullable()->after('source_path');
      }
    });
  }

  public function down(): void
  {
    Schema::table('backup_schedules', function (Blueprint $table) {
      if (Schema::hasColumn('backup_schedules', 'include')) {
        $table->dropColumn('include');
      }
    });
  }
};
