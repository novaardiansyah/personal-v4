<?php

use App\Http\Controllers\Api\NoteController;
use Illuminate\Support\Facades\Route;

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