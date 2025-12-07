<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\DownloadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
  return redirect('admin', 301);
});

Route::get('download/{path}/{extension}', [DownloadController::class, 'index'])
  ->name('download')
  ->middleware('signed');
  
Route::get('admin/activity-logs/{activityLog}/preview-email', [ActivityLogController::class, 'preview_email']);