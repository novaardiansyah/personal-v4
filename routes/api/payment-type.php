<?php

/*
 * Project Name: personal-v4
 * File: payment-types.php
 * Created Date: Sunday February 22nd 2026
 * 
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 * 
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

declare(strict_types=1);

use App\Http\Controllers\Api\PaymentTypeController;
use Illuminate\Support\Facades\Route;

Route::prefix('payment-types')->group(function () {
  Route::get('/', [PaymentTypeController::class, 'index']);
});
