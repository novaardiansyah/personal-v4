<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShortUrl;

class ShortUrlController extends Controller
{
  public function redirect($short_code)
  {
    $shortUrl = ShortUrl::where('short_code', $short_code)
      ->where('is_active', true)
      ->orderBy('id', 'desc')
      ->first();

    if (!$shortUrl) {
      return response()->json([
        'success' => false,
        'message' => 'Short URL not found or inactive'
      ], 404);
    }

    // Increment click count
    $shortUrl->increment('clicks');

    return response()->json([
      'success' => true,
      'data' => [
        'id'         => $shortUrl->id,
        'short_code' => $shortUrl->short_code,
        'long_url'   => $shortUrl->long_url,
        'updated_at' => $shortUrl->updated_at,
      ]
    ]);
  }
}
