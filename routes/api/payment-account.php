<?php

/*
 * Project Name: personal-v4
 * File: payment-accounts.php
 * Created Date: Sunday February 22nd 2026
 * 
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 * 
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

declare(strict_types=1);

use App\Http\Controllers\Api\PaymentAccountController;
use Illuminate\Support\Facades\Route;

Route::prefix('payment-accounts')->group(function () {
  Route::get('/', [PaymentAccountController::class, 'index']);
  Route::get('/{paymentAccount}', [PaymentAccountController::class, 'show']);
  Route::post('/', [PaymentAccountController::class, 'store']);
  Route::put('/{id}', [PaymentAccountController::class, 'update']);
  Route::delete('/{paymentAccount}', [PaymentAccountController::class, 'destroy']);
  Route::post('/{paymentAccount}/audit', [PaymentAccountController::class, 'audit']);

  Route::post('/report-monthly', [PaymentAccountController::class, 'reportMonthly']);
});
