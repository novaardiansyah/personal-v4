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
#[OA\Get(
  path: "/api/health",
  summary: "Health Check",
  tags: ["Health"],
  responses: [new OA\Response(response: 200, description: "OK")]
)]
abstract class Controller
{
}
