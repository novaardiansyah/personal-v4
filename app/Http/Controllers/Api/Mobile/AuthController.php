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

use function Illuminate\Log\log;

class AuthController extends Controller
{
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
        'token' => $token,
        'user' => [
          'id'         => $user->id,
          'name'       => $user->name,
          'email'      => $user->email,
          'avatar_url' => $user->avatar_url,
        ]
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
        'token' => $token,
        'user' => [
          'id' => $user->id,
          'name' => $user->name,
          'email' => $user->email,
          'avatar_url' => $user->avatar_url,
        ]
      ]
    ], 201);
  }
}
