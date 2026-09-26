<?php

/*
 * Project Name: personal-v4
 * File: AuthController.php (Mobile API)
 * Created Date: Saturday September 26th 2026
 *
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 *
 * Copyright (c) 2025-2026 Nova Ardiansyah, Org
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
  /**
   * Format user array with full URL avatar.
   */
  private function formatUserResponse(User $user): array
  {
    $avatarUrl = $user->avatar_url;
    if (!empty($avatarUrl)) {
      if (!str_starts_with($avatarUrl, 'http://') && !str_starts_with($avatarUrl, 'https://')) {
        $avatarUrl = Storage::disk('rustfs')->url($avatarUrl);
      }
    }

    return [
      'id'         => $user->id,
      'name'       => $user->name,
      'email'      => $user->email,
      'avatar_url' => $avatarUrl ?: null,
    ];
  }

  public function login(Request $request)
  {
    $email    = trim((string) $request->input('email', ''));
    $password = (string) $request->input('password', '');

    if (empty($email) || empty($password)) {
      return response()->json([
        'success' => false,
        'message' => 'Email dan kata sandi wajib diisi.',
        'errors' => [
          'email'    => empty($email) ? ['Email wajib diisi.'] : [],
          'password' => empty($password) ? ['Kata sandi wajib diisi.'] : [],
        ]
      ], 422);
    }

    $user = User::where('email', $email)->first();

    if (!$user) {
      return response()->json([
        'success' => false,
        'message' => 'Email tidak terdaftar dalam sistem.',
        'errors' => [
          'email' => ['Email ini belum terdaftar.']
        ]
      ], 404);
    }

    if (!Hash::check($password, $user->password)) {
      return response()->json([
        'success' => false,
        'message' => 'Kata sandi yang Anda masukkan salah.',
        'errors' => [
          'password' => ['Kata sandi tidak sesuai.']
        ]
      ], 401);
    }

    $expiration = Carbon::now()->addDays(7);
    $token      = $user->createToken('mobile_auth_token', ['*'], $expiration)->plainTextToken;

    event(new Login('api', $user, false));

    return response()->json([
      'success' => true,
      'message' => 'Login berhasil',
      'data' => [
        'token'      => $token,
        'user'       => $this->formatUserResponse($user),
        'expires_at' => $expiration->toIso8601String(),
      ]
    ]);
  }

  public function register(Request $request)
  {
    $name = trim((string) $request->input('name', ''));
    $email = trim((string) $request->input('email', ''));
    $password = (string) $request->input('password', '');

    if (empty($name) || empty($email) || empty($password)) {
      return response()->json([
        'success' => false,
        'message' => 'Data pendaftaran belum lengkap.',
        'errors' => [
          'name' => empty($name) ? ['Nama lengkap wajib diisi.'] : [],
          'email' => empty($email) ? ['Email wajib diisi.'] : [],
          'password' => empty($password) ? ['Kata sandi wajib diisi.'] : [],
        ]
      ], 422);
    }

    $emailExists = User::where('email', $email)->exists();
    if ($emailExists) {
      return response()->json([
        'success' => false,
        'message' => 'Email sudah terdaftar.',
        'errors' => [
          'email' => ['Email ini sudah digunakan oleh akun lain.']
        ]
      ], 422);
    }

    $user = User::create([
      'name' => $name,
      'email' => $email,
      'password' => Hash::make($password),
    ]);

    $expiration = Carbon::now()->addDays(7);
    $token = $user->createToken('mobile_auth_token', ['*'], $expiration)->plainTextToken;

    return response()->json([
      'success' => true,
      'message' => 'Pendaftaran akun berhasil',
      'data' => [
        'token'      => $token,
        'user'       => $this->formatUserResponse($user),
        'expires_at' => $expiration->toIso8601String(),
      ]
    ], 201);
  }

  public function profile(Request $request)
  {
    $user = $request->user();

    if (!$user) {
      return response()->json([
        'success' => false,
        'message' => 'Unauthorized.',
      ], 401);
    }

    return response()->json([
      'success' => true,
      'message' => 'Data profil pengguna',
      'data' => [
        'user' => $this->formatUserResponse($user),
      ]
    ]);
  }

  public function updateProfile(Request $request)
  {
    $user = $request->user();

    if (!$user) {
      return response()->json([
        'success' => false,
        'message' => 'Unauthorized.',
      ], 401);
    }

    $name           = trim((string) $request->input('name', ''));
    $email          = trim((string) $request->input('email', ''));
    $avatarBase64   = $request->input('avatar_base64');
    $avatarUrlInput = $request->input('avatar_url');

    if (empty($name)) {
      return response()->json([
        'success' => false,
        'message' => 'Nama lengkap wajib diisi.',
        'errors' => [
          'name' => ['Nama lengkap wajib diisi.']
        ]
      ], 422);
    }

    if (!empty($email) && $email !== $user->email) {
      $emailExists = User::where('email', $email)->where('id', '!=', $user->id)->exists();
      if ($emailExists) {
        return response()->json([
          'success' => false,
          'message' => 'Email sudah digunakan oleh akun lain.',
          'errors' => [
            'email' => ['Email ini sudah digunakan oleh akun lain.']
          ]
        ], 422);
      }
      $user->email = $email;
    }

    $user->name = $name;

    if (!empty($avatarBase64)) {
      if ($user->avatar_url && !str_starts_with($user->avatar_url, 'http')) {
        $oldPath = str_replace(Storage::disk('rustfs')->url(''), '', $user->avatar_url);
        Storage::disk('rustfs')->delete($oldPath);
      }

      $path = processBase64Image($avatarBase64, 'images/avatar', 'rustfs');
      if ($path) {
        $user->avatar_url = $path;
      }
    } elseif ($request->has('avatar_url') && empty($avatarUrlInput)) {
      if ($user->avatar_url && !str_starts_with($user->avatar_url, 'http')) {
        $oldPath = str_replace(Storage::disk('rustfs')->url(''), '', $user->avatar_url);
        Storage::disk('rustfs')->delete($oldPath);
      }
      $user->avatar_url = null;
    }

    $user->save();

    return response()->json([
      'success' => true,
      'message' => 'Profil berhasil diperbarui.',
      'data' => [
        'user' => $this->formatUserResponse($user),
      ]
    ]);
  }

  public function changePassword(Request $request)
  {
    $user = $request->user();

    if (!$user) {
      return response()->json([
        'success' => false,
        'message' => 'Unauthorized.',
      ], 401);
    }

    $currentPassword         = (string) $request->input('current_password', '');
    $newPassword             = (string) $request->input('new_password', '');
    $newPasswordConfirmation = (string) $request->input('new_password_confirmation', '');

    $errors = [];
    if (empty($currentPassword)) {
      $errors['current_password'] = ['Kata sandi saat ini wajib diisi.'];
    }
    if (empty($newPassword)) {
      $errors['new_password'] = ['Kata sandi baru wajib diisi.'];
    } elseif (strlen($newPassword) < 6) {
      $errors['new_password'] = ['Kata sandi baru minimal 6 karakter.'];
    }
    if ($newPassword !== $newPasswordConfirmation) {
      $errors['new_password_confirmation'] = ['Konfirmasi kata sandi baru tidak cocok.'];
    }

    if (!empty($errors)) {
      return response()->json([
        'success' => false,
        'message' => 'Data penggantian kata sandi tidak valid.',
        'errors'  => $errors,
      ], 422);
    }

    if (!Hash::check($currentPassword, $user->password)) {
      return response()->json([
        'success' => false,
        'message' => 'Kata sandi saat ini tidak sesuai.',
        'errors'  => [
          'current_password' => ['Kata sandi saat ini salah.']
        ]
      ], 400);
    }

    $user->password = Hash::make($newPassword);
    $user->save();

    $expiration = Carbon::now()->addDays(7);
    $token      = $user->createToken('mobile_auth_token', ['*'], $expiration)->plainTextToken;

    return response()->json([
      'success' => true,
      'message' => 'Kata sandi berhasil diperbarui.',
      'data'    => [
        'token'      => $token,
        'user'       => $this->formatUserResponse($user),
        'expires_at' => $expiration->toIso8601String(),
      ]
    ]);
  }

  public function logout(Request $request)
  {
    $user = $request->user();
    if ($user && $user->currentAccessToken()) {
      $user->currentAccessToken()->delete();
    }

    return response()->json([
      'success' => true,
      'message' => 'Logout berhasil.',
    ]);
  }
}

