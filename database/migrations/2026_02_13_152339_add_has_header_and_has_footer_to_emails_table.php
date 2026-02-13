<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::table('emails', function (Blueprint $table) {
      $table->boolean('has_header')->default(true);
      $table->boolean('has_footer')->default(true);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('emails', function (Blueprint $table) {
      $table->dropColumn('has_header');
      $table->dropColumn('has_footer');
    });
  }
};
