<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
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

    $expiration = config('sanctum.expiration') ? now()->addMinutes(config('sanctum.expiration')) : null;
    $token = $user->createToken('auth_token', ['*'], $expiration)->plainTextToken;

    event(new Login('api', $user, false));

    // \Log::info('786 --> spy token', ['token' => $token]);

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
    $expiration = config('sanctum.expiration') ? now()->addMinutes(config('sanctum.expiration')) : null;
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
          'id'   => $user->id,
          'cpde' => $user->code,
          'name' => $user->name,
        ]
      ]
    ]);
  }

  public function updateProfile(UpdateProfileRequest $request)
  {
    $user = $request->user();

    $validated = $request->validated();

    if (!empty($validated['avatar_base64'])) {
      $base64Data = $validated['avatar_base64'];

      if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $matches)) {
        $extension = strtolower($matches[1]);
        $base64Image = substr($base64Data, strpos($base64Data, ',') + 1);
        $imageData = base64_decode($base64Image);

        if ($imageData !== false) {
          $path = 'images/avatar/' . Str::random(25) . '.' . $extension;

          Storage::disk('public')->put($path, $imageData);

          if ($user->avatar_url) {
            $oldPath = str_replace(Storage::url(''), '', $user->avatar_url);
            Storage::disk('public')->delete($oldPath);
          }

          $validated['avatar_url'] = $path;
        }
      }
    }

    $user->update($validated);

    $user = $user->fresh();
    $user->avatar_url = Storage::disk('public')->url($user->avatar_url);

    return response()->json([
      'success' => true,
      'message' => 'Profile updated successfully',
      'data' => [
        'user' => $user
      ]
    ]);
  }
}
