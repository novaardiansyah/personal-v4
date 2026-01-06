<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class PushNotificationController extends Controller
{
  #[OA\Post(
    path: "/api/push-notifications",
    summary: "Create new push notification",
    description: "Create a new push notification for a specific user. The notification data will automatically include a timestamp.",
    tags: ["Push Notifications"],
    security: [["bearerAuth" => []]],
    requestBody: new OA\RequestBody(
      required: true,
      content: new OA\JsonContent(
        required: ["title", "body"],
        properties: [
          new OA\Property(property: "title", type: "string", maxLength: 255, description: "Notification title", example: "New Message"),
          new OA\Property(property: "body", type: "string", description: "Notification message body", example: "You have a new message from admin"),
          new OA\Property(
            property: "data",
            type: "object",
            description: "Additional data to include with the notification (optional)",
            example: ["action" => "open_chat", "chat_id" => 123]
          )
        ]
      )
    ),
    responses: [
      new OA\Response(
        response: 201,
        description: "Push notification created successfully",
        content: new OA\JsonContent(
          properties: [
            new OA\Property(property: "success", type: "boolean", example: true),
            new OA\Property(property: "message", type: "string", example: "Push notification created successfully"),
            new OA\Property(
              property: "data",
              type: "object",
              properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "user_id", type: "integer", example: 1),
                new OA\Property(property: "token", type: "string", example: "ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]"),
                new OA\Property(property: "title", type: "string", example: "New Message"),
                new OA\Property(property: "body", type: "string", example: "You have a new message from admin"),
                new OA\Property(
                  property: "data",
                  type: "object",
                  example: ["action" => "open_chat", "chat_id" => 123, "timestamps" => "2025-12-18 00:49:00"]
                ),
                new OA\Property(property: "sent_at", type: "string", nullable: true, example: null),
                new OA\Property(property: "error_message", type: "string", nullable: true, example: null),
                new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2025-12-18T00:49:00.000000Z"),
                new OA\Property(property: "updated_at", type: "string", format: "date-time", example: "2025-12-18T00:49:00.000000Z")
              ]
            )
          ]
        )
      ),
      new OA\Response(
        response: 422,
        description: "Validation error",
        content: new OA\JsonContent(
          properties: [
            new OA\Property(property: "success", type: "boolean", example: false),
            new OA\Property(property: "message", type: "string", example: "Validation failed"),
            new OA\Property(
              property: "errors",
              type: "object",
              properties: [
                new OA\Property(property: "title", type: "array", items: new OA\Items(type: "string", example: "The notification title field is required.")),
                new OA\Property(property: "body", type: "array", items: new OA\Items(type: "string", example: "The message body field is required."))
              ]
            )
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
  public function store(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'title' => 'required|string|max:255',
      'body' => 'required|string',
      'data' => 'nullable|array',
    ]);

    $validator->setAttributeNames([
      'user_id' => 'user id',
      'title' => 'notification title',
      'body' => 'message body',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $validator->errors()
      ], 422);
    }

    $data = $validator->validated();

    $user = Auth::user();

    if (!$user->has_allow_notification) {
      return response()->json([
        'success' => false,
        'message' => 'User has disabled notifications'
      ], 422);
    }

    $additionalData = $data['data'] ?? [];
    $additionalData['timestamps'] = now()->toDateTimeString();

    $pushNotification = PushNotification::create([
      'user_id' => Auth::id(),
      'token' => $user->notification_token,
      'title' => $data['title'],
      'body' => $data['body'],
      'data' => $additionalData,
    ]);

    return response()->json([
      'success' => true,
      'message' => 'Push notification created successfully',
      'data' => $pushNotification
    ], 201);
  }
}
