<?php

namespace App\Swagger;

/**
 * Auth Request/Response Schemas
 *
 * @OA\Schema(
 *     schema="UpdateProfileRequest",
 *     required={"name"},
 *     @OA\Property(property="name", type="string", example="John Doe", description="User's full name"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com", description="User's email address"),
 *     @OA\Property(property="avatar_base64", type="string", example="data:image/png;base64,...", description="Base64 encoded avatar image")
 * )
 *
 * @OA\Schema(
 *     schema="UpdateProfileResponse",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Profile updated successfully"),
 *     @OA\Property(property="data", type="object",
 *         @OA\Property(property="user", type="object",
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="name", type="string", example="John Doe"),
 *             @OA\Property(property="email", type="string", example="john@example.com"),
 *             @OA\Property(property="avatar_url", type="string", example="http://example.com/storage/images/avatar/image.png")
 *         )
 *     )
 * )
 */
class AuthSchemas
{
}
