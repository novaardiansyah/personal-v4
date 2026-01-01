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
    Schema::table('emails', function (Blueprint $table) {
      $table->dropColumn('attachment_urls');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('emails', function (Blueprint $table) {
      $table->json('attachment_urls')->nullable();
    });
  }
};
