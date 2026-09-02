<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('discord_messages', function (Blueprint $table) {
      $table->json('content')->nullable()->change();
      $table->json('response')->nullable()->change();
    });
  }

  public function down(): void
  {
    Schema::table('discord_messages', function (Blueprint $table) {
      $table->text('content')->nullable()->change();
      $table->text('response')->nullable()->change();
    });
  }
};
