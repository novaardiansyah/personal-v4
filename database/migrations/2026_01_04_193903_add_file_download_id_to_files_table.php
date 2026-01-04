<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::table('files', function (Blueprint $table) {
      $table->foreignId('file_download_id')->nullable()->after('id')->constrained('file_downloads')->cascadeOnDelete();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('files', function (Blueprint $table) {
      $table->dropForeign(['file_download_id']);
      $table->dropIndex(['file_download_id']);
      $table->dropColumn('file_download_id');
    });
  }
};
