<?php

namespace App\Swagger;

use OpenApi\Annotations as OA;

/**
 * Common Response Schemas
 *
 * @OA\Schema(
 *     schema="UnauthenticatedResponse",
 *     @OA\Property(property="message", type="string", example="Unauthenticated.")
 * )
 *
 * @OA\Schema(
 *     schema="ValidationErrorResponse",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Validation failed"),
 *     @OA\Property(property="errors", type="object",
 *         example={"field_name": {"The field_name field is required."}}
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="SuccessResponse",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Operation successful"),
 *     @OA\Property(property="data", type="object")
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Operation failed")
 * )
 */
class Schemas
{
}
