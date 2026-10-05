<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\SystemBackup;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
  public function login(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'email'    => 'required|email',
      'password' => 'required|string',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation error',
        'errors'  => $validator->errors(),
      ], 422);
    }

    $user = User::where('email', $request->email)->first();

    if (! $user || ! Hash::check($request->password, $user->password)) {
      return response()->json([
        'success' => false,
        'message' => 'Invalid credentials',
      ], 401);
    }

    $expiration = Carbon::now()->addHour();
    $token      = $user->createToken('system_backup_token', ['*'], $expiration)->plainTextToken;

    event(new Login('api', $user, false));

    return response()->json([
      'success' => true,
      'message' => 'Login successful',
      'data'    => [
        'token'      => $token,
        'token_type' => 'Bearer',
        'expires_at' => $expiration->toIso8601String(),
        'user'       => [
          'id'    => $user->id,
          'name'  => $user->name,
          'email' => $user->email,
        ],
      ],
    ]);
  }

  public function user(Request $request): JsonResponse
  {
    return response()->json([
      'success' => true,
      'data'    => $request->user(),
    ]);
  }

  public function logout(Request $request): JsonResponse
  {
    $request->user()->currentAccessToken()->delete();

    return response()->json([
      'success' => true,
      'message' => 'Successfully logged out',
    ]);
  }
}
