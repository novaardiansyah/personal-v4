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
    Schema::create('short_urls', function (Blueprint $table) {
      $table->id();
      $table->string('code')->unique();
      $table->string('qrcode')->nullable();
      $table->string('note')->nullable();
      $table->string('long_url');
      $table->string('short_url');
      $table->string('tiny_url')->nullable();
      $table->boolean('is_active')->default(true);
      $table->unsignedInteger('clicks')->default(0);
      $table->softDeletes();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('short_urls');
  }
};
