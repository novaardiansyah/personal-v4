<?php

use App\Http\Controllers\Api\CalendarCategoryController;
use App\Http\Controllers\Api\CalendarEventController;
use App\Http\Controllers\Api\CalendarTodoController;
use Illuminate\Support\Facades\Route;

Route::prefix('events')->group(function () {
    Route::get('/', [CalendarEventController::class, 'index']);
    Route::post('/', [CalendarEventController::class, 'store']);

    Route::get('upcoming', [CalendarEventController::class, 'upcoming']);

    Route::get('/{event}', [CalendarEventController::class, 'show'])
        ->whereNumber('event');

    Route::get('/{code}', [CalendarEventController::class, 'showByCode'])
        ->where('code', '[A-Z0-9\-]+');

    Route::put('/{code}', [CalendarEventController::class, 'update'])
        ->where('code', '[A-Z0-9\-]+');

    Route::delete('/{code}', [CalendarEventController::class, 'destroy'])
        ->where('code', '[A-Z0-9\-]+');

    Route::delete('/{id}/force', [CalendarEventController::class, 'forceDestroy']);
    Route::post('/{id}/restore', [CalendarEventController::class, 'restore']);
    Route::patch('/{id}/duplicate', [CalendarEventController::class, 'duplicate']);
});

Route::prefix('todos')->group(function () {
    Route::get('/', [CalendarTodoController::class, 'index']);
    Route::post('/', [CalendarTodoController::class, 'store']);

    Route::get('/{todo}', [CalendarTodoController::class, 'show'])
        ->whereNumber('todo');

    Route::get('/{code}', [CalendarTodoController::class, 'showByCode'])
        ->where('code', '[A-Z0-9\-]+');

    Route::put('/{code}', [CalendarTodoController::class, 'update'])
        ->where('code', '[A-Z0-9\-]+');

    Route::delete('/{code}', [CalendarTodoController::class, 'destroy'])
        ->where('code', '[A-Z0-9\-]+');

    Route::delete('/{id}/force', [CalendarTodoController::class, 'forceDestroy']);
    Route::post('/{id}/restore', [CalendarTodoController::class, 'restore']);
    Route::patch('/{id}/toggle', [CalendarTodoController::class, 'toggle']);
});

Route::prefix('calendar')->group(function () {
    Route::get('/upcoming', [CalendarEventController::class, 'upcoming']);
    Route::get('/export', [CalendarEventController::class, 'export']);

    Route::prefix('categories')->group(function () {
        Route::get('/', [CalendarCategoryController::class, 'index']);
        Route::post('/', [CalendarCategoryController::class, 'store']);
        Route::put('/{id}', [CalendarCategoryController::class, 'update']);
        Route::delete('/{id}', [CalendarCategoryController::class, 'destroy']);
    });
});
