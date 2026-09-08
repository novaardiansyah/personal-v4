<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('discord_messages', function (Blueprint $table) {
      $table->id();
      $table->uuid('uid')->nullable()->unique();
      $table->text('content');
      $table->string('status')->default('pending');
      $table->text('response')->nullable();
      $table->softDeletes();
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('discord_messages');
  }
};
