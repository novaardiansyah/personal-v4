<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Schema(
  schema: "UnauthenticatedResponse",
  properties: [
    new OA\Property(property: "message", type: "string", example: "Unauthenticated.")
  ]
)]
#[OA\Schema(
  schema: "ValidationErrorResponse",
  properties: [
    new OA\Property(property: "success", type: "boolean", example: false),
    new OA\Property(property: "message", type: "string", example: "Validation failed"),
    new OA\Property(
      property: "errors",
      type: "object",
      example: ["field_name" => ["The field_name field is required."]]
    )
  ]
)]
#[OA\Schema(
  schema: "SuccessResponse",
  properties: [
    new OA\Property(property: "success", type: "boolean", example: true),
    new OA\Property(property: "message", type: "string", example: "Operation successful"),
    new OA\Property(property: "data", type: "object")
  ]
)]
#[OA\Schema(
  schema: "ErrorResponse",
  properties: [
    new OA\Property(property: "success", type: "boolean", example: false),
    new OA\Property(property: "message", type: "string", example: "Operation failed")
  ]
)]
class Schemas
{
}
