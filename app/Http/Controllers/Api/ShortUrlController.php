<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShortUrl;
use App\Models\ActivityLog;

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

    // Log activity with IP and user agent
    $this->logShortUrlAccess($shortUrl);

    return response()->json([
      'success' => true,
      'data' => [
        'id'         => $shortUrl->id,
        'short_code' => $shortUrl->short_code,
        'long_url'   => $shortUrl->long_url,
        'total_clicks' => $shortUrl->clicks,
        'updated_at' => $shortUrl->updated_at,
      ]
    ]);
  }

  private function logShortUrlAccess(ShortUrl $shortUrl): void
  {
    $ip_address = request()->ip();
    $user_agent = request()->userAgent();

    $ipInfo = getIpInfo($ip_address);

    $country     = $ipInfo['country'];
    $city        = $ipInfo['city'];
    $region      = $ipInfo['region'];
    $postal      = $ipInfo['postal'];
    $geolocation = $ipInfo['geolocation'];
    $timezone    = $ipInfo['timezone'];
    $user        = getUser();

    saveActivityLog([
      'description'  => 'Short URL accessed by ' . $user->name,
      'event'        => 'Access',
      'subject_id'   => $shortUrl->id,
      'subject_type' => ShortUrl::class,
      'ip_address'   => $ip_address,
      'country'      => $country,
      'city'         => $city,
      'region'       => $region,
      'postal'       => $postal,
      'geolocation'  => $geolocation,
      'timezone'     => $timezone,
      'user_agent'   => $user_agent,
      'properties'   => [
        'short_code' => $shortUrl->short_code,
        'long_url'   => $shortUrl->long_url,
        'click_count' => $shortUrl->clicks,
      ],
    ]);
  }
}
