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
    Schema::create('push_notifications', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->onDelete('cascade');
      $table->string('title');
      $table->text('body');
      $table->json('data')->nullable();
      $table->string('token')->nullable();
      $table->text('error_message')->nullable();
      $table->json('response_data')->nullable();
      $table->timestamp('sent_at')->nullable();
      $table->timestamp('delivered_at')->nullable();
      $table->softDeletes();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('push_notifications');
  }
};
