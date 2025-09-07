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
    Schema::create('payments', function (Blueprint $table) {
      $table->id();
      $table->foreignId('type_id')->constrained('payment_types')->cascadeOnDelete();
      $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
      $table->foreignId('payment_account_id')->constrained('payment_accounts')->cascadeOnDelete();
      $table->foreignId('payment_account_to_id')->nullable()->constrained('payment_accounts')->onDelete('cascade');
      $table->string('code')->nullable();
      $table->text('name')->nullable();
      $table->bigInteger('amount')->default(0);
      $table->boolean('has_items')->default(false);
      $table->date('date')->nullable();
      $table->boolean('is_scheduled')->default(false);
      $table->text('attachments')->nullable();
      $table->softDeletes();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('payments');
  }
};
