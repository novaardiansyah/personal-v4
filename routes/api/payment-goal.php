<?php

/*
 * Project Name: personal-v4
 * File: payment-goals.php
 * Created Date: Sunday February 22nd 2026
 * 
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 * 
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

declare(strict_types=1);

use App\Http\Controllers\Api\PaymentGoalController;
use Illuminate\Support\Facades\Route;

Route::prefix('payment-goals')->group(function () {
  Route::get('/', [PaymentGoalController::class, 'index']);
  Route::get('/overview', [PaymentGoalController::class, 'overview']);
  Route::get('/statistics', [PaymentGoalController::class, 'statistics']);
  Route::post('/', [PaymentGoalController::class, 'store']);
  Route::get('/{paymentGoal}', [PaymentGoalController::class, 'show']);
  Route::put('/{paymentGoal}', [PaymentGoalController::class, 'update']);
  Route::delete('/{paymentGoal}', [PaymentGoalController::class, 'destroy']);
  Route::post('/{paymentGoal}/restore', [PaymentGoalController::class, 'restore']);
  Route::delete('/{paymentGoal}/force', [PaymentGoalController::class, 'forceDestroy']);
  Route::put('/{paymentGoal}/progress', [PaymentGoalController::class, 'updateProgress']);
});