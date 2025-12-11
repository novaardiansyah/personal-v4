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
    Schema::create('blog_categories', function (Blueprint $table) {
      $table->id();
      $table->string('name')->nullable();
      $table->string('slug')->unique()->nullable();
      $table->unsignedInteger('usage_count')->default(0);
      $table->text('description')->nullable();
      $table->string('color_hex')->default('#3B82F6');
      $table->unsignedInteger('display_order')->default(0);
      $table->softDeletes();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('blog_categories');
  }
};
