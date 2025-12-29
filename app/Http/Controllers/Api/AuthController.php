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
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
  /**
   * @OA\Post(
   *     path="/api/auth/login",
   *     summary="Login",
   *     tags={"Auth"},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"email", "password"},
   *             @OA\Property(property="email", type="string", format="email", example="user@example.com", description="User's email address"),
   *             @OA\Property(property="password", type="string", format="password", example="password123", description="User's password (min 6 characters)")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Login successful",
   *         @OA\JsonContent(
   *             @OA\Property(property="success", type="boolean", example=true),
   *             @OA\Property(property="message", type="string", example="Login successful"),
   *             @OA\Property(property="data", type="object",
   *                 @OA\Property(property="token", type="string", example="1|abc123xyz...")
   *             )
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Invalid credentials",
   *         @OA\JsonContent(
   *             @OA\Property(property="success", type="boolean", example=false),
   *             @OA\Property(property="message", type="string", example="Invalid credentials")
   *         )
   *     ),
   *     @OA\Response(
   *         response=422,
   *         description="Validation error",
   *         @OA\JsonContent(
   *             @OA\Property(property="success", type="boolean", example=false),
   *             @OA\Property(property="message", type="string", example="Validation error"),
   *             @OA\Property(property="errors", type="object")
   *         )
   *     )
   * )
   */
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

  /**
   * @OA\Post(
   *     path="/api/auth/change-password",
   *     summary="Change Password",
   *     tags={"Auth"},
   *     security={{"bearerAuth":{}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"current_password", "new_password"},
   *             @OA\Property(property="current_password", type="string", format="password", example="oldpassword123"),
   *             @OA\Property(property="new_password", type="string", format="password", example="newpassword123")
   *         )
   *     ),
   *     @OA\Response(response=200, description="Password changed successfully", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
   *     @OA\Response(response=400, description="Current password is incorrect", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse"))
   * )
   */
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

  /**
   * @OA\Post(
   *     path="/api/auth/logout",
   *     summary="Logout",
   *     tags={"Auth"},
   *     security={{"bearerAuth":{}}},
   *     @OA\Response(
   *         response=200,
   *         description="Logout successful",
   *         @OA\JsonContent(
   *             @OA\Property(property="success", type="boolean", example=true),
   *             @OA\Property(property="message", type="string", example="Logout successful. Current access token has been revoked.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthenticated",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Unauthenticated.")
   *         )
   *     )
   * )
   */
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

  /**
   * @OA\Post(
   *     path="/api/auth/update-profile",
   *     summary="Update Profile",
   *     tags={"Auth"},
   *     security={{"bearerAuth":{}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(ref="#/components/schemas/UpdateProfileRequest")
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Profile updated successfully",
   *         @OA\JsonContent(ref="#/components/schemas/UpdateProfileResponse")
   *     ),
   *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse")),
   *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
   * )
   */
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

      Log::info('7590 --> [short-url-token] GET ' . $path . ' status: ' . $response->status());
    } catch (\Exception $e) {
      Log::info('7880 --> [short-url-token] Error request: ' . $e->getMessage());
    }
  }
}
