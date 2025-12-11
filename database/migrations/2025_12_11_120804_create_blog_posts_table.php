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
    Schema::create('blog_posts', function (Blueprint $table) {
      $table->id();
      $table->string('title');
      $table->string('slug')->unique();
      $table->text('excerpt')->nullable();
      $table->longText('content');
      $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
      $table->foreignId('category_id')->nullable()->constrained('blog_categories')->nullOnDelete();
      $table->string('cover_image_url')->nullable();
      $table->string('cover_image_alt')->nullable();
      $table->string('meta_title')->nullable();
      $table->string('meta_description')->nullable();
      $table->enum('status', ['draft', 'published', 'scheduled', 'archived'])->default('draft');
      $table->timestamp('published_at')->nullable();
      $table->timestamp('scheduled_at')->nullable();
      $table->integer('display_order')->default(0);
      $table->integer('view_count')->default(0);
      $table->softDeletes();
      $table->timestamps();
      
      $table->index('status');
      $table->index('published_at');
      $table->index('display_order');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('blog_posts');
  }
};
