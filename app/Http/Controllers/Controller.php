<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
  version: "1.0.0",
  title: "Personal V4 API Documentation",
  description: "API documentation for Personal V4 application",
  contact: new OA\Contact(email: "admin@novaardiansyah.id")
)]
#[OA\Server(url: "/", description: "API Server")]
#[OA\SecurityScheme(
  securityScheme: "bearerAuth",
  type: "http",
  scheme: "bearer",
  bearerFormat: "JWT"
)]
abstract class Controller
{
}
