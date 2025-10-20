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
    Schema::create('payment_goals', function (Blueprint $table) {
      $table->id();
      $table->foreignId('status_id')->constrained('payment_goal_statuses')->cascadeOnDelete();
      $table->string('code')->unique();
      $table->string('name')->nullable();
      $table->string('description')->nullable();
      $table->integer('amount')->default(0);
      $table->integer('target_amount')->default(0);
      $table->integer('progress_percent')->default(0);
      $table->date('start_date')->nullable();
      $table->date('target_date')->nullable();
      $table->softDeletes();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('payment_goals');
  }
};
