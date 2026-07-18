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
    Schema::create('subscriptions', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
      $table->string('code')->nullable();
      $table->string('name');
      $table->bigInteger('amount')->default(0);
      $table->foreignId('payment_account_id')->constrained('payment_accounts')->cascadeOnDelete();
      $table->foreignId('category_id')->nullable()->constrained('payment_categories')->noActionOnDelete();
      $table->enum('cycle', ['monthly', 'quarterly', 'yearly'])->default('monthly');
      $table->date('next_date');
      $table->unsignedSmallInteger('reminder_days_before')->default(3);
      $table->boolean('is_paused')->default(false);
      $table->timestamp('last_reminded_at')->nullable();
      $table->softDeletes();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('subscriptions');
  }
};