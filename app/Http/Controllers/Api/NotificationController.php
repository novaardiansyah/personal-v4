<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ExpoNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
  protected ExpoNotificationService $notificationService;

  public function __construct(ExpoNotificationService $notificationService)
  {
    $this->notificationService = $notificationService;
  }

  /**
   * Update notification settings
   */
  public function updateNotificationSettings(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'has_allow_notification' => 'sometimes|boolean',
      'notification_token' => 'sometimes|string|max:255|nullable',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation error',
        'errors' => $validator->errors()
      ], 422);
    }

    $user = $request->user();
    $validated = $validator->validated();

    // Validate Expo token format if provided
    if (isset($validated['notification_token']) && $validated['notification_token']) {
      if (!$this->notificationService->validateToken($validated['notification_token'])) {
        return response()->json([
          'success' => false,
          'message' => 'Invalid Expo push token format'
        ], 400);
      }
    }

    $user->update($validated);

    return response()->json([
      'success' => true,
      'message' => 'Notification settings updated successfully',
    ]);
  }

  /**
   * Test notification endpoint
   */
  public function testNotification(Request $request)
  {
    $user = Auth::user();

    $record = $user->pushNotifications()->create([
      'title' => 'Test Notification',
      'body' => 'This is a test notification from your Laravel app!',
    ]);

    $result = sendPushNotification($user, $record);

    if (!$result['success']) {
      return response()->json($result, 400);
    }

    return response()->json($result, 200);
  }
}
