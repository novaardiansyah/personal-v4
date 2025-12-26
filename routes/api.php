<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ContactMessageController;
use App\Http\Controllers\Api\GenerateController;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SkillController;
use App\Http\Controllers\Api\ShortUrlController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PushNotificationController;

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
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
  });

  Route::prefix('skills')->group(function () {
    Route::get('/', [SkillController::class, 'index']);
    Route::get('/{id}', [SkillController::class, 'show']);
  });

  Route::prefix('short-urls')->group(function () {
    Route::get('/', [ShortUrlController::class, 'index']);
    Route::post('/', [ShortUrlController::class, 'store']);
    Route::get('/{short_code}', [ShortUrlController::class, 'redirect']);
  });

  require __DIR__ . '/api/payment.php';

  Route::prefix('notifications')->group(function () {
    Route::put('/settings', [NotificationController::class, 'updateNotificationSettings']);
    Route::post('/test', [NotificationController::class, 'testNotification']);
  });

  Route::prefix('generates')->group(function () {
    Route::post('/', [GenerateController::class, 'getCode']);
  });

  Route::prefix('contact-messages')->group(function () {
    Route::post('/', [ContactMessageController::class, 'store']);
  });

  require __DIR__ . '/api/blog.php';

  Route::prefix('push-notifications')->group(function () {
    Route::post('/', [PushNotificationController::class, 'store']);
  });

  require __DIR__ . '/api/note.php';
});

