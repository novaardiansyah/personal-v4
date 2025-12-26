<?php

use App\Http\Controllers\Api\BlogCategoryController;
use App\Http\Controllers\Api\BlogPostController;
use App\Http\Controllers\Api\BlogSubscriberController;
use App\Http\Controllers\Api\BlogTagController;
use Illuminate\Support\Facades\Route;

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