<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('backup_jobs', function (Blueprint $table) {
      $table->foreignId('storage_id')->nullable()->constrained('backup_storages')->noActionOnDelete();
    });
  }

  public function down(): void
  {
    Schema::table('backup_jobs', function (Blueprint $table) {
      $table->dropForeign(['storage_id']);
      $table->dropColumn('storage_id');
    });
  }
};
