<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V2\BackupController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('backups')->group(function () {
  Route::get('/', [BackupController::class, 'index']);
  Route::post('/', [BackupController::class, 'store']);
  Route::get('/{id}', [BackupController::class, 'show']);
  Route::put('/{id}', [BackupController::class, 'update']);
  Route::patch('/{id}', [BackupController::class, 'update']);
  Route::delete('/{id}', [BackupController::class, 'destroy']);
  Route::post('/{id}/restore', [BackupController::class, 'restore']);
  Route::get('/{id}/download', [BackupController::class, 'download']);
});
