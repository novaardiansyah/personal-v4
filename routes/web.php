<?php

/*
 * Project Name: personal-v4
 * File: web.php
 * Created Date: Sunday February 1st 2026
 *
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 *
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\WebhookController;
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

Route::get('admin/email-templates/{emailTemplate}/preview', [EmailTemplateController::class, 'preview'])
  ->name('admin.email-templates.preview')
  ->middleware('auth');

Route::prefix('webhook')->group(function () {
  Route::post('/testing', [WebhookController::class, 'testing']);

  Route::prefix('notifications')->group(function () {
    Route::post('/telegram', [WebhookController::class, 'telegram']);
  });
});
