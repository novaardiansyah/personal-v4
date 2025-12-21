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
use App\Http\Controllers\Api\GalleryController;
use App\Http\Controllers\Api\GalleryTagController;
use App\Http\Controllers\Api\SkillController;
use App\Http\Controllers\Api\ShortUrlController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PaymentAccountController;
use App\Http\Controllers\Api\PaymentTypeController;
use App\Http\Controllers\Api\ItemTypeController;
use App\Http\Controllers\Api\PaymentGoalController;
use App\Http\Controllers\Api\PaymentGoalStatusController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PushNotificationController;
use App\Http\Controllers\Api\NoteController;

Route::post('/auth/login', [AuthController::class, 'login']);

Route::prefix('blog-subscribers')->group(function () {
  Route::get('/subscribe/{email}', [BlogSubscriberController::class, 'subscribe']);
  Route::get('/verify/{token}', [BlogSubscriberController::class, 'verify']);
  Route::get('/unsubscribe/{token}', [BlogSubscriberController::class, 'unsubscribe']);
  Route::get('/re-subscribe/{token}', [BlogSubscriberController::class, 'reSubscribe']);
  Route::get('/{token}', [BlogSubscriberController::class, 'show']);
});

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
    Route::get('/', [ShortUrlController::class, 'index']);
    Route::post('/', [ShortUrlController::class, 'store']);
    Route::get('/{short_code}', [ShortUrlController::class, 'redirect']);
  });

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
    Route::post('/{id}/items/create-attach', [PaymentController::class, 'createAndAttachItem']);
    Route::post('/{payment}/items/attach-multiple', [PaymentController::class, 'attachMultipleItems']);
    Route::put('/{payment}/items/{pivotId}', [PaymentController::class, 'updateItem']);
    Route::delete('/{payment}/items/{pivotId}', [PaymentController::class, 'detachItem']);

    Route::get('/{payment}/attachments', [PaymentController::class, 'getAttachments']);
    Route::post('/{payment}/attachments', [PaymentController::class, 'addAttachment']);
    Route::delete('/{payment}/attachments', [PaymentController::class, 'deleteAttachment']);

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

