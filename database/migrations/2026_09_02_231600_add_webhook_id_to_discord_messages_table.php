<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('discord_messages', function (Blueprint $table) {
      $table->foreignId('webhook_id')->after('uid')->constrained('discord_webhooks')->noActionOnDelete();
    });
  }

  public function down(): void
  {
    Schema::table('discord_messages', function (Blueprint $table) {
      $table->dropForeign(['webhook_id']);
      $table->dropColumn('webhook_id');
    });
  }
};
