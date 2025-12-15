<?php

namespace App\Swagger;

/**
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="Sanctum",
 *     description="Enter your Bearer token in the format: Bearer {token}"
 * )
 */
class Security
{
}
