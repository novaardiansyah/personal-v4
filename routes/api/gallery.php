<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\GalleryController;

Route::prefix('galleries')->group(function () {
  Route::get('/', [GalleryController::class, 'index']);
  Route::post('/', [GalleryController::class, 'store']);
  Route::get('/{gallery}', [GalleryController::class, 'show']);
  Route::delete('/{gallery}', [GalleryController::class, 'destroy']);
  Route::delete('/{id}/force', [GalleryController::class, 'forceDelete']);
});