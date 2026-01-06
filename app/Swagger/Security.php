<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\SecurityScheme(
  securityScheme: "bearerAuth",
  type: "http",
  scheme: "bearer",
  bearerFormat: "Sanctum",
  description: "Enter the token with the Bearer prefix, e.g. `Bearer abcde12345`"
)]
class Security
{
}
