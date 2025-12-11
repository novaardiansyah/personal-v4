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
    Schema::create('blog_tags', function (Blueprint $table) {
      $table->id();
      $table->string('name')->nullable();
      $table->string('slug')->unique()->nullable();
      $table->unsignedInteger('usage_count')->default(0);
      $table->text('description')->nullable();
      $table->softDeletes();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('blog_tags');
  }
};
