<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('calendar_categories', function (Blueprint $table) {
      $table->string('code')->nullable()->after('id');
    });
  }

  public function down(): void
  {
    Schema::table('calendar_categories', function (Blueprint $table) {
      $table->dropColumn('code');
    });
  }
};
