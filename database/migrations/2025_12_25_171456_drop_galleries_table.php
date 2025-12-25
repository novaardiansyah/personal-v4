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
    Schema::dropIfExists('galleries');
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::create('galleries', function (Blueprint $table) {
      $table->id();
      $table->string('image');
      $table->string('description')->nullable();
      $table->string('url')->nullable();
      $table->boolean('is_publish')->default(false);
      $table->softDeletes();
      $table->timestamps();
    });
  }
};
