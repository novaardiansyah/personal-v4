<?php

use App\Http\Controllers\Api\Mobile\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
  Route::get('/auth/user', [AuthController::class, 'profile']);
  Route::get('/auth/profile', [AuthController::class, 'profile']);
  Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
  Route::post('/auth/profile', [AuthController::class, 'updateProfile']);
  Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
  Route::post('/auth/logout', [AuthController::class, 'logout']);
});
