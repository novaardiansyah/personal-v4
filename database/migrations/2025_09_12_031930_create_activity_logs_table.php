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
    Schema::create('activity_logs', function (Blueprint $table) {
      $table->id();
      $table->string('log_name')->nullable();
      $table->text('description')->nullable();
      $table->nullableMorphs('subject');
      $table->string('event')->nullable();
      $table->nullableMorphs('causer');
      $table->longText('prev_properties')->nullable();
      $table->longText('properties')->nullable();
      $table->char('batch_uuid', 36)->nullable();
      $table->softDeletes();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('activity_logs');
  }
};
