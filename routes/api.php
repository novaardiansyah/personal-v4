<?php

use App\Http\Controllers\Api\BlogCategoryController;
use App\Http\Controllers\Api\BlogPostController;
use App\Http\Controllers\Api\BlogSubscriberController;
use App\Http\Controllers\Api\BlogTagController;
use App\Http\Controllers\Api\ContactMessageController;
use App\Http\Controllers\Api\GenerateController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Api\AuthController;

use App\Http\Controllers\Api\SkillController;
use App\Http\Controllers\Api\ShortUrlController;

use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PushNotificationController;
use App\Http\Controllers\Api\NoteController;

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

  Route::prefix('blog-categories')->group(function () {
    Route::get('/', [BlogCategoryController::class, 'index']);
    Route::get('/{id}', [BlogCategoryController::class, 'show']);
  });

  Route::prefix('blog-tags')->group(function () {
    Route::get('/', [BlogTagController::class, 'index']);
    Route::get('/{id}', [BlogTagController::class, 'show']);
  });

  Route::prefix('blog-posts')->group(function () {
    Route::get('/published', [BlogPostController::class, 'published']);
    Route::get('/{blogPost:slug}', [BlogPostController::class, 'showBySlug']);
  });

  Route::prefix('blog-subscribers')->group(function () {
    Route::post('/subscribe', [BlogSubscriberController::class, 'subscribe']);
    Route::post('/verify', [BlogSubscriberController::class, 'verify']);
    Route::post('/unsubscribe', [BlogSubscriberController::class, 'unsubscribe']);
    Route::post('/re-subscribe', [BlogSubscriberController::class, 'reSubscribe']);
    Route::get('/{token}', [BlogSubscriberController::class, 'show']);
  });

  Route::prefix('push-notifications')->group(function () {
    Route::post('/', [PushNotificationController::class, 'store']);
  });

  Route::prefix('notes')->group(function () {
    Route::get('/', [NoteController::class, 'index']);
    Route::post('/', [NoteController::class, 'store']);

    Route::get('/{note}', [NoteController::class, 'show'])
      ->whereNumber('note');

    Route::get('/{code}', [NoteController::class, 'showByCode'])
      ->where('code', '[A-Z0-9\\-]+');

    Route::put('/{code}', [NoteController::class, 'update'])
      ->where('code', '[A-Z0-9\\-]+');

    Route::delete('/{code}', [NoteController::class, 'destroy'])
      ->where('code', '[A-Z0-9\\-]+');

    Route::delete('/{note}/force', [NoteController::class, 'forceDestroy']);
    Route::post('/{note}/restore', [NoteController::class, 'restore']);
    Route::patch('/{note}/toggle-pin', [NoteController::class, 'togglePin']);
    Route::patch('/{note}/toggle-archive', [NoteController::class, 'toggleArchive']);
  });
});

