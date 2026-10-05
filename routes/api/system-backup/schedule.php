<?php

use App\Http\Controllers\Api\SystemBackup\BackupScheduleController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
  Route::get('/schedules', [BackupScheduleController::class, 'index']);
});
