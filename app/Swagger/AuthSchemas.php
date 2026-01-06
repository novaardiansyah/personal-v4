<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Schema(
  schema: "UpdateProfileRequest",
  required: ["name"],
  properties: [
    new OA\Property(property: "name", type: "string", example: "John Doe", description: "User's full name"),
    new OA\Property(property: "email", type: "string", format: "email", example: "john@example.com", description: "User's email address"),
    new OA\Property(property: "avatar_base64", type: "string", example: "data:image/png;base64,...", description: "Base64 encoded avatar image")
  ]
)]
#[OA\Schema(
  schema: "UpdateProfileResponse",
  properties: [
    new OA\Property(property: "success", type: "boolean", example: true),
    new OA\Property(property: "message", type: "string", example: "Profile updated successfully"),
    new OA\Property(
      property: "data",
      type: "object",
      properties: [
        new OA\Property(
          property: "user",
          type: "object",
          properties: [
            new OA\Property(property: "id", type: "integer", example: 1),
            new OA\Property(property: "name", type: "string", example: "John Doe"),
            new OA\Property(property: "email", type: "string", example: "john@example.com"),
            new OA\Property(property: "avatar_url", type: "string", example: "http://example.com/storage/images/avatar/image.png")
          ]
        )
      ]
    )
  ]
)]
#[OA\Schema(
  schema: "ChangePasswordRequest",
  required: ["current_password", "new_password", "new_password_confirmation"],
  properties: [
    new OA\Property(property: "current_password", type: "string", format: "password", example: "oldpassword123"),
    new OA\Property(property: "new_password", type: "string", format: "password", example: "newpassword123"),
    new OA\Property(property: "new_password_confirmation", type: "string", format: "password", example: "newpassword123")
  ]
)]
#[OA\Schema(
  schema: "ChangePasswordResponse",
  properties: [
    new OA\Property(property: "success", type: "boolean", example: true),
    new OA\Property(property: "message", type: "string", example: "Password changed successfully"),
    new OA\Property(
      property: "data",
      type: "object",
      properties: [
        new OA\Property(property: "token", type: "string", example: "1|abc123xyz...")
      ]
    )
  ]
)]
class AuthSchemas
{
}
