<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V2\FileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('files')->group(function () {
  Route::get('/', [FileController::class, 'index']);
  Route::post('/', [FileController::class, 'store']);
  Route::get('/{id}', [FileController::class, 'show']);
  Route::put('/{id}', [FileController::class, 'update']);
  Route::patch('/{id}', [FileController::class, 'update']);
  Route::delete('/{id}', [FileController::class, 'destroy']);
});
