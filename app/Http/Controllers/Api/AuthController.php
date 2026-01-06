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
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
  #[OA\Post(
    path: "/api/auth/login",
    summary: "Login",
    tags: ["Auth"],
    requestBody: new OA\RequestBody(
      required: true,
      content: new OA\JsonContent(
        required: ["email", "password"],
        properties: [
          new OA\Property(property: "email", type: "string", format: "email", example: "user@example.com", description: "User's email address"),
          new OA\Property(property: "password", type: "string", format: "password", example: "password123", description: "User's password (min 6 characters)")
        ]
      )
    ),
    responses: [
      new OA\Response(
        response: 200,
        description: "Login successful",
        content: new OA\JsonContent(
          properties: [
            new OA\Property(property: "success", type: "boolean", example: true),
            new OA\Property(property: "message", type: "string", example: "Login successful"),
            new OA\Property(property: "data", type: "object", properties: [
              new OA\Property(property: "token", type: "string", example: "1|abc123xyz...")
            ])
          ]
        )
      ),
      new OA\Response(
        response: 401,
        description: "Invalid credentials",
        content: new OA\JsonContent(
          properties: [
            new OA\Property(property: "success", type: "boolean", example: false),
            new OA\Property(property: "message", type: "string", example: "Invalid credentials")
          ]
        )
      ),
      new OA\Response(
        response: 422,
        description: "Validation error",
        content: new OA\JsonContent(
          properties: [
            new OA\Property(property: "success", type: "boolean", example: false),
            new OA\Property(property: "message", type: "string", example: "Validation error"),
            new OA\Property(property: "errors", type: "object")
          ]
        )
      )
    ]
  )]
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

  #[OA\Post(
    path: "/api/auth/change-password",
    summary: "Change Password",
    tags: ["Auth"],
    security: [["bearerAuth" => []]],
    requestBody: new OA\RequestBody(
      required: true,
      content: new OA\JsonContent(
        required: ["current_password", "new_password", "new_password_confirmation"],
        properties: [
          new OA\Property(property: "current_password", type: "string", example: "oldpassword123"),
          new OA\Property(property: "new_password", type: "string", example: "newpassword123"),
          new OA\Property(property: "new_password_confirmation", type: "string", example: "newpassword123")
        ]
      )
    ),
    responses: [
      new OA\Response(response: 200, description: "Password changed successfully"),
      new OA\Response(response: 400, description: "Current password is incorrect"),
      new OA\Response(response: 401, description: "Unauthenticated"),
      new OA\Response(response: 422, description: "Validation error")
    ]
  )]
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

  #[OA\Post(
    path: "/api/auth/logout",
    summary: "Logout",
    tags: ["Auth"],
    security: [["bearerAuth" => []]],
    responses: [
      new OA\Response(
        response: 200,
        description: "Logout successful",
        content: new OA\JsonContent(
          properties: [
            new OA\Property(property: "success", type: "boolean", example: true),
            new OA\Property(property: "message", type: "string", example: "Logout successful. Current access token has been revoked.")
          ]
        )
      ),
      new OA\Response(
        response: 401,
        description: "Unauthenticated",
        content: new OA\JsonContent(
          properties: [
            new OA\Property(property: "message", type: "string", example: "Unauthenticated.")
          ]
        )
      )
    ]
  )]
  public function logout(Request $request)
  {
    $user = $request->user();

    $user->currentAccessToken()->delete();

    return response()->json([
      'success' => true,
      'message' => 'Logout successful. Current access token has been revoked.'
    ]);
  }

  #[OA\Post(
    path: "/api/auth/update-profile",
    summary: "Update Profile",
    tags: ["Auth"],
    security: [["bearerAuth" => []]],
    requestBody: new OA\RequestBody(
      required: true,
      content: new OA\JsonContent(
        required: ["name"],
        properties: [
          new OA\Property(property: "name", type: "string", example: "John Doe"),
          new OA\Property(property: "email", type: "string", format: "email", example: "john@example.com"),
          new OA\Property(property: "avatar_base64", type: "string", nullable: true)
        ]
      )
    ),
    responses: [
      new OA\Response(response: 200, description: "Profile updated successfully"),
      new OA\Response(response: 401, description: "Unauthenticated"),
      new OA\Response(response: 422, description: "Validation error")
    ]
  )]
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
