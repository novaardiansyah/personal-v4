<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('backups', function (Blueprint $table) {
      if (!Schema::hasColumn('backups', 'cloud_file_path')) {
        $table->string('cloud_file_path')->nullable()->after('file_path');
      }
    });
  }

  public function down(): void
  {
    Schema::table('backups', function (Blueprint $table) {
      if (Schema::hasColumn('backups', 'cloud_file_path')) {
        $table->dropColumn('cloud_file_path');
      }
    });
  }
};
