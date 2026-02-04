<?php

/*
 * Project Name: personal-v4
 * File: WebhookController.php
 * Created Date: Wednesday February 4th 2026
 *
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 *
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WebhookController extends Controller
{
  public function testing(Request $request)
  {
    $signature = $request->header('X-Signature');

    if (!$signature) {
      return response()->json(['message' => 'Missing signature'], 422);
    }

    $validator = Validator::make($request->all(), [
      'timestamp' => ['required', Rule::date()->format('Y-m-d H:i:s')],
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation error',
        'errors' => $validator->errors()
      ], 422);
    }

    $validated = $validator->validated();
    $expectedSignature = hash_hmac('sha256', $validated['timestamp'], config('services.self.webhook_secret'));

    if ($expectedSignature !== $signature) {
      return response()->json([
        'success' => false,
        'message' => 'Invalid signature',
      ], 422);
    }

    return response()->json([
      'success' => true,
      'message' => 'Webhook testing successful',
    ]);
  }
}
