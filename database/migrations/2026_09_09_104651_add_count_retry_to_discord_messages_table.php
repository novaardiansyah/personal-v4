<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('discord_messages', function (Blueprint $table) {
      $table->unsignedInteger('count_retry')->default(0)->after('response');
    });
  }

  public function down(): void
  {
    Schema::table('discord_messages', function (Blueprint $table) {
      $table->dropColumn('count_retry');
    });
  }
};
