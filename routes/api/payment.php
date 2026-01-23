<?php

use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PaymentAccountController;
use App\Http\Controllers\Api\PaymentTypeController;
use App\Http\Controllers\Api\ItemTypeController;
use App\Http\Controllers\Api\PaymentGoalController;
use App\Http\Controllers\Api\PaymentGoalStatusController;
use Illuminate\Support\Facades\Route;

Route::prefix('payments')->group(function () {
  Route::post('/generate-report', [PaymentController::class, 'generateReport']);
  Route::get('/ai-summary', [PaymentController::class, 'aiSummary']);
  Route::get('/summary', [PaymentController::class, 'summary']);
  Route::get('/types', [PaymentController::class, 'getPaymentTypes']);
  Route::get('/item-types', [PaymentController::class, 'getItemTypes']);

  Route::get('/', [PaymentController::class, 'index']);
  Route::post('/', [PaymentController::class, 'store']);

  Route::get('/{id}', [PaymentController::class, 'show'])
    ->whereNumber('id');

  Route::get('/{code}', [PaymentController::class, 'showByCode'])
    ->where('code', '[A-Z0-9\-]+');

  Route::put('/{id}', [PaymentController::class, 'update']);
  Route::delete('/{payment}', [PaymentController::class, 'destroy']);

  Route::get('/{id}/items/attached', [PaymentController::class, 'getAttachedItems']);
  Route::get('/{payment}/items/summary', [PaymentController::class, 'getPaymentItemsSummary']);
  Route::get('/{id}/items/not-attached', [PaymentController::class, 'getItemsNotAttached']);
  Route::get('/{id}/items/available', [PaymentController::class, 'getAvailableItems']);
  Route::post('/{id}/items/attach', [PaymentController::class, 'attachItem']);
  Route::post('/{payment}/items/create-attach', [PaymentController::class, 'createAndAttachItem']);
  Route::post('/{payment}/items/attach-multiple', [PaymentController::class, 'attachMultipleItems']);
  Route::put('/{payment}/items/{pivotId}', [PaymentController::class, 'updateItem']);
  Route::delete('/{payment}/items/{pivotId}', [PaymentController::class, 'detachItem']);

  Route::post('/{code}/manage-draft', [PaymentController::class, 'manageDraft']);
});

Route::prefix('payment-accounts')->group(function () {
  Route::get('/', [PaymentAccountController::class, 'index']);
  Route::get('/{paymentAccount}', [PaymentAccountController::class, 'show']);
  Route::post('/', [PaymentAccountController::class, 'store']);
  Route::put('/{id}', [PaymentAccountController::class, 'update']);
  Route::delete('/{paymentAccount}', [PaymentAccountController::class, 'destroy']);
  Route::post('/{paymentAccount}/audit', [PaymentAccountController::class, 'audit']);

  Route::post('/report-monthly', [PaymentAccountController::class, 'reportMonthly']);
});

Route::prefix('payment-types')->group(function () {
  Route::get('/', [PaymentTypeController::class, 'index']);
});

Route::prefix('item-types')->group(function () {
  Route::get('/', [ItemTypeController::class, 'index']);
});

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

Route::prefix('payment-goal-statuses')->group(function () {
  Route::get('/', [PaymentGoalStatusController::class, 'index']);
});
