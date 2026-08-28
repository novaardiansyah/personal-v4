<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V2\BackupController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('backups')->group(function () {
  Route::get('/check-schedule', [BackupController::class, 'checkSchedule']);
  Route::post('/jobs/{id}/report', [BackupController::class, 'storeJobReport']);
  Route::get('/storages/{backupStorage}', [BackupController::class, 'showStorage']);
});
