<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Info(
  version: "1.0.0",
  title: "API Documentation",
  description: "This is an official Personal-v4 API documentation.",
  termsOfService: "https://novaardiansyah.id/live/nova-app/terms-of-service",
  contact: new OA\Contact(
    name: "API Support",
    url: "https://novaardiansyah.id",
    email: "support@novaardiansyah.id"
  ),
  license: new OA\License(
    name: "MIT License",
    url: "https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE"
  )
)]
#[OA\Server(
  url: "https://personal-v4.novaardiansyah.id",
  description: "Production Server"
)]
#[OA\Server(
  url: "http://100.108.9.46:8000",
  description: "Development Server"
)]
class Info
{
}
