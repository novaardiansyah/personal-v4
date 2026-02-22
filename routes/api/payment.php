<?php

use App\Http\Controllers\Api\PaymentController;
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
