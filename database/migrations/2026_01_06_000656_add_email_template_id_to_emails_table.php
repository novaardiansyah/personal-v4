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
      $table->foreignId('email_template_id')->nullable()->constrained()->cascadeOnDelete();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('emails', function (Blueprint $table) {
      $table->dropForeign(['email_template_id']);
      $table->dropColumn('email_template_id');
    });
  }
};
