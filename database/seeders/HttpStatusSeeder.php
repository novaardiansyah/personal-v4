<?php

namespace Database\Seeders;

use App\Models\HttpStatus;
use Illuminate\Database\Seeder;

class HttpStatusSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $statuses = [
      ['name' => 200, 'message' => 'OK', 'description' => 'The request has succeeded.'],
      ['name' => 201, 'message' => 'Created', 'description' => 'The request has been fulfilled and resulted in a new resource.'],
      ['name' => 204, 'message' => 'No Content', 'description' => 'The server successfully processed the request but is not returning any content.'],
      ['name' => 301, 'message' => 'Moved Permanently', 'description' => 'The requested resource has been permanently moved to a new URL.'],
      ['name' => 302, 'message' => 'Found', 'description' => 'The requested resource has been temporarily moved to a new URL.'],
      ['name' => 304, 'message' => 'Not Modified', 'description' => 'The resource has not been modified since the last request.'],
      ['name' => 400, 'message' => 'Bad Request', 'description' => 'The request could not be understood or was missing required parameters.'],
      ['name' => 401, 'message' => 'Unauthorized', 'description' => 'Authentication is required and has failed or has not yet been provided.'],
      ['name' => 403, 'message' => 'Forbidden', 'description' => 'The server understood the request but refuses to authorize it.'],
      ['name' => 404, 'message' => 'Not Found', 'description' => 'The requested resource could not be found on the server.'],
      ['name' => 422, 'message' => 'Unprocessable Entity', 'description' => 'The request was well-formed but contained semantic errors.'],
      ['name' => 429, 'message' => 'Too Many Requests', 'description' => 'The user has sent too many requests in a given amount of time.'],
      ['name' => 500, 'message' => 'Internal Server Error', 'description' => 'The server encountered an unexpected condition that prevented it from fulfilling the request.'],
      ['name' => 502, 'message' => 'Bad Gateway', 'description' => 'The server, while acting as a gateway or proxy, received an invalid response from the upstream server.'],
      ['name' => 503, 'message' => 'Service Unavailable', 'description' => 'The server is currently unable to handle the request due to temporary overloading or maintenance.'],
    ];

    foreach ($statuses as $status) {
      HttpStatus::create($status);
    }
  }
}
