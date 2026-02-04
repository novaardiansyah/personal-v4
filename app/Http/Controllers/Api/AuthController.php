<?php

/*
 * Project Name: personal-v4
 * File: AuthController.php
 * Created Date: Thursday December 11th 2025
 *
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 *
 * Copyright (c) 2025-2026 Nova Ardiansyah, Org
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Carbon;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Events\Login;

class AuthController extends Controller
{
  public function login(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'email' => 'required|email',
      'password' => 'required|string|min:6',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation error',
        'errors' => $validator->errors()
      ], 422);
    }

    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
      return response()->json([
        'success' => false,
        'message' => 'Invalid credentials'
      ], 401);
    }

    $expiration = Carbon::now()->addDays(7);
    $token = $user->createToken('auth_token', ['*'], $expiration)->plainTextToken;

    event(new Login('api', $user, false));

    return response()->json([
      'success' => true,
      'message' => 'Login successful',
      'data' => [
        'token' => $token
      ]
    ]);
  }

  public function changePassword(Request $request)
  {
    $user = auth()->user();

    $validator = Validator::make($request->all(), [
      'current_password' => 'required|string|min:6',
      'new_password' => 'required|string|min:6|confirmed',
    ]);

    $validator->setAttributeNames([
      'current_password' => 'kata sandi saat ini',
      'new_password' => 'kata sandi baru',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $validator->errors()
      ], 422);
    }

    $validated = $validator->validated();

    if (!Hash::check($validated['current_password'], $user->password)) {
      return response()->json([
        'success' => false,
        'message' => 'Current password is incorrect'
      ], 400);
    }

    $user->password = Hash::make($validated['new_password']);
    $user->save();

    $user->tokens()->delete();

    $expiration = Carbon::now()->addDays(7);
    $newToken = $user->createToken('auth_token', ['*'], $expiration)->plainTextToken;

    return response()->json([
      'success' => true,
      'message' => 'Password changed successfully',
      'data' => [
        'token' => $newToken
      ]
    ]);
  }

  public function validateToken(Request $request)
  {
    $user = $request->user();

    return response()->json([
      'success' => true,
      'message' => 'Token is valid',
      'data' => [
        'user' => [
          'id' => $user->id,
          'cpde' => $user->code,
          'name' => $user->name,
        ]
      ]
    ]);
  }

  public function logout(Request $request)
  {
    $user = $request->user();

    $user->currentAccessToken()->delete();

    return response()->json([
      'success' => true,
      'message' => 'Logout successful. Current access token has been revoked.'
    ]);
  }

  public function updateProfile(Request $request)
  {
    $user = auth()->user();

    $validator = Validator::make($request->all(), [
      'name' => 'required|string|max:255',
      'email' => 'sometimes|email|max:255|unique:users,email,' . $user->id,
      'avatar_base64' => 'sometimes|nullable|string',
    ]);

    $validator->setAttributeNames([
      'name' => 'nama lengkap',
      'email' => 'email',
      'avatar_base64' => 'avatar',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $validator->errors()
      ], 422);
    }

    $validated = $validator->validated();

    if (!empty($validated['avatar_base64'])) {
      if ($user->avatar_url) {
        $oldPath = str_replace(Storage::url(''), '', $user->avatar_url);
        Storage::disk('public')->delete($oldPath);
      }

      $path = processBase64Image($validated['avatar_base64'], 'images/avatar');

      if ($path) {
        $validated['avatar_url'] = $path;
      }
    }

    $user->update($validated);

    $user = $user->fresh();
    $user->avatar_url = asset("storage/{$user->avatar_url}");

    return response()->json([
      'success' => true,
      'message' => 'Profile updated successfully',
      'data' => [
        'user' => $user
      ]
    ]);
  }
}
