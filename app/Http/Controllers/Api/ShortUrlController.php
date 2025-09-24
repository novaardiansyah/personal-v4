<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShortUrl;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Http;

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

    $ip_address = explode(',', $ip_address)[0] ?? '127.0.0.2';
    $url        = getSetting('ipinfo_api_url');

    $replace = [
      'ip_address' => $ip_address,
      'token'      => config(key: 'services.ipinfo.token')
    ];

    foreach ($replace as $key => $value) {
      $url = str_replace('{' . $key . '}', $value, $url);
    }

    $ip_info = Http::get($url)->json();

    $country = $ip_info['country'] ?? null;
    $city    = $ip_info['city'] ?? null;
    $region  = $ip_info['region'] ?? null;
    $postal  = $ip_info['postal'] ?? null;
    $geolocation = $ip_info['loc'] ?? null;
    $geolocation = $geolocation ? str_replace(',', ', ', $geolocation) : null;
    $timezone    = $ip_info['timezone'] ?? null;

    saveActivityLog([
      'description'  => 'Short URL accessed',
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
