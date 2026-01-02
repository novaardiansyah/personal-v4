<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\EmailController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
  return redirect('admin', 301);
});

Route::get('download/{path}/{extension}', [DownloadController::class, 'index'])
  ->name('download')
  ->middleware('signed');

Route::get('admin/activity-logs/{activityLog}/preview-email', [ActivityLogController::class, 'preview_email']);

Route::get('admin/emails/{email}/preview', [EmailController::class, 'preview'])
  ->name('admin.emails.preview')
  ->middleware('auth');
