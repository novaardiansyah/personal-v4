<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
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

  public function changePassword(ChangePasswordRequest $request)
  {
    $user = $request->user();

    if (!Hash::check($request->current_password, $user->password)) {
      return response()->json([
        'success' => false,
        'message' => 'Current password is incorrect'
      ], 400);
    }

    $user->password = Hash::make($request->new_password);
    $user->save();

    $user->tokens()->delete();
    
    $expiration = Carbon::now()->addDays(7);
    $newToken   = $user->createToken('auth_token', ['*'], $expiration)->plainTextToken;

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

    // ! Delete only the current token
    $user->currentAccessToken()->delete();

    return response()->json([
      'success' => true,
      'message' => 'Logout successful. Current access token has been revoked.'
    ]);
  }

  public function updateProfile(UpdateProfileRequest $request)
  {
    $user = $request->user();

    $validated = $request->validated();

    if (!empty($validated['avatar_base64'])) {
      // Delete old avatar if exists
      if ($user->avatar_url) {
        $oldPath = str_replace(Storage::url(''), '', $user->avatar_url);
        Storage::disk('public')->delete($oldPath);
      }

      // Process new avatar
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

  public function monitorToken()
  {
    $token = config('services.self.short_url_token');

    try {
      if (!$token) {
        throw new \Exception('SHORTURL_TOKEN is not set in the environment variables.');
      }

      $path = '/api/short-urls/BaoS6Ws';

      $response = Http::withToken($token)
        ->get('https://personal-v4.novadev.my.id' . $path);

      \Log::info('7590 --> [short-url-token] GET '. $path .' status: ' . $response->status());
    } catch (\Exception $e) {
      \Log::info('7880 --> [short-url-token] Error request: ' . $e->getMessage());
    }
  }
}
