<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GalleryController;
use App\Http\Controllers\Api\GalleryTagController;
use App\Http\Controllers\Api\SkillController;
use App\Http\Controllers\Api\ShortUrlController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PaymentAccountController;
use App\Http\Controllers\Api\PaymentTypeController;
use App\Http\Controllers\Api\ItemTypeController;

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
  Route::get('/user', function (Request $request) {
    $user = $request->user();
    if ($user) {
      $user->avatar_url = Storage::disk('public')->url($user->avatar_url);
    }
    return $user;
  });

  Route::prefix('auth')->group(function () {
    Route::get('/validate-token', [AuthController::class, 'validateToken']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
  });

  Route::prefix('skills')->group(function () {
    Route::get('/', [SkillController::class, 'index']);
    Route::get('/{id}', [SkillController::class, 'show']);
  });

  Route::prefix('gallery-tags')->group(function () {
    Route::get('/', [GalleryTagController::class, 'index']);
    Route::get('/{id}', [GalleryTagController::class, 'show']);
  });

  Route::prefix('galleries')->group(function () {
    Route::get('/', [GalleryController::class, 'index']);
    Route::get('/{id}', [GalleryController::class, 'show']);
    Route::get('/tag/{tagId}', [GalleryController::class, 'getByTag']);
  });

  Route::prefix('short-urls')->group(function () {
    Route::get('/{short_code}', [ShortUrlController::class, 'redirect']);
  });

  Route::prefix('payments')->group(function () {
    Route::get('/summary', [PaymentController::class, 'summary']);
    Route::get('/recent-transactions', [PaymentController::class, 'recentTransactions']);
    Route::get('/', [PaymentController::class, 'index']);
    Route::post('/', [PaymentController::class, 'store']);
    Route::get('/{id}', [PaymentController::class, 'show']);
    Route::put('/{id}', [PaymentController::class, 'update']);
    Route::delete('/{id}', [PaymentController::class, 'destroy']);
    Route::get('/types', [PaymentController::class, 'getPaymentTypes']);

    // Item management for payments
    Route::get('/{id}/items/attached', [PaymentController::class, 'getAttachedItems']);
    Route::get('/{id}/items/not-attached', [PaymentController::class, 'getItemsNotAttached']);
    Route::get('/{id}/items/available', [PaymentController::class, 'getAvailableItems']);
    Route::get('/item-types', [PaymentController::class, 'getItemTypes']);
    Route::post('/{id}/items/attach', [PaymentController::class, 'attachItem']);
    Route::post('/{id}/items/create-attach', [PaymentController::class, 'createAndAttachItem']);
    Route::post('/{payment}/items/attach-multiple', [PaymentController::class, 'attachMultipleItems']);
    Route::delete('/{id}/items/{pivotId}', [PaymentController::class, 'detachItem']);
  });

  Route::prefix('payment-accounts')->group(function () {
    Route::get('/', [PaymentAccountController::class, 'index']);
    Route::post('/', [PaymentAccountController::class, 'store']);
    Route::put('/{id}', [PaymentAccountController::class, 'update']);
    Route::delete('/{id}', [PaymentAccountController::class, 'destroy']);
  });

  Route::prefix('payment-types')->group(function () {
    Route::get('/', [PaymentTypeController::class, 'index']);
  });

  Route::prefix('item-types')->group(function () {
    Route::get('/', [ItemTypeController::class, 'index']);
  });
});
