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
    Schema::create('generates', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->string('alias')->nullable();
      $table->string('prefix')->nullable();
      $table->string('suffix')->nullable();
      $table->string('separator')->default(now()->translatedFormat('ymd'));
      $table->unsignedInteger('queue')->default(0);
      $table->softDeletes();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('generates');
  }
};
