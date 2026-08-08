<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('backups', function (Blueprint $table) {
      $table->unsignedBigInteger('file_size')->nullable()->change();
    });
  }

  public function down(): void
  {
    Schema::table('backups', function (Blueprint $table) {
      $table->unsignedInteger('file_size')->nullable()->change();
    });
  }
};
