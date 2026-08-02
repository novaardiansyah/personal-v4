<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('backup_schedules', function (Blueprint $table) {
      if (!Schema::hasColumn('backup_schedules', 'keep_local_backup')) {
        $table->boolean('keep_local_backup')->default(true)->after('is_sync_cloud');
      }
    });
  }

  public function down(): void
  {
    Schema::table('backup_schedules', function (Blueprint $table) {
      if (Schema::hasColumn('backup_schedules', 'keep_local_backup')) {
        $table->dropColumn('keep_local_backup');
      }
    });
  }
};
