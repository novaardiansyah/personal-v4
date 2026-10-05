<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    if (Schema::hasTable('backups') && Schema::hasColumn('backups', 'server_name')) {
      Schema::table('backups', function (Blueprint $table) {
        $table->dropColumn('server_name');
      });
    }
  }

  public function down(): void
  {
    Schema::table('backups', function (Blueprint $table) {
      $table->string('server_name')->nullable();
    });
  }
};
