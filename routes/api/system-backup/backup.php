<?php

declare(strict_types=1);

use App\Http\Controllers\Api\SystemBackup\BackupController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
  Route::post('/backups', [BackupController::class, 'store']);
});
