<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('backup_storage_providers', function (Blueprint $table) {
      $table->json('keys')->nullable()->after('slug');
    });
  }

  public function down(): void
  {
    Schema::table('backup_storage_providers', function (Blueprint $table) {
      $table->dropColumn('keys');
    });
  }
};
