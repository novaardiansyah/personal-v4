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
      $table->string('subject_type')->nullable()->change();
      $table->unsignedBigInteger('subject_id')->nullable()->change();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('files', function (Blueprint $table) {
      $table->dropColumn('subject_type');
      $table->dropColumn('subject_id');
    });
  }
};
