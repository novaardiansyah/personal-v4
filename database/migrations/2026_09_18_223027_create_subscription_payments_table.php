<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('subscription_payments', function (Blueprint $table) {
      $table->id();
      $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
      $table->foreignId('payment_id')->constrained('payments')->noActionOnDelete();
      $table->string('period');
      $table->timestamps();

      $table->unique(['subscription_id', 'period']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('subscription_payments');
  }
};
