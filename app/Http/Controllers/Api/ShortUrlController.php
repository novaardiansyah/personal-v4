<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ShortUrlCollection;
use App\Models\ShortUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Api\ShortUrlResource;
use Illuminate\Support\Str;

class ShortUrlController extends Controller
{
  public function index()
  {
    $shortUrls = ShortUrl::orderBy('updated_at', 'desc')->paginate();
    return response()->json(new ShortUrlCollection($shortUrls));
  }

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

  public function store(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'long_url' => [
        'required',
        'url',
        'max:150',
        'regex:/^https?:\/\/.+\..+$/'
      ],
      'note'      => 'nullable|string|max:150',
      'is_active' => 'boolean'
    ], [
      'long_url.regex' => 'The long URL format is invalid.',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors'  => $validator->errors()
      ], 422);
    }

    $sanitizedData = [
      'long_url'  => Str::trim($request->long_url),
      'note'      => Str::squish(strip_tags($request->note)),
      'is_active' => $request->boolean('is_active', true),
    ];

    $exist = ShortUrl::where('long_url', $sanitizedData['long_url'])
      ->where('user_id', auth()->user()->id)
      ->first();
      
    if ($exist) {
      $exist->code = getCode('short_url');
      $exist->note = $sanitizedData['note'];
      $exist->save();

      return response()->json([
        'success' => true,
        'message' => 'Short URL created successfully',
        'data'    => new ShortUrlResource($exist)
      ], 201);
    }

    $shortUrl = ShortUrl::createShortUrl([
      'long_url'  => $sanitizedData['long_url'],
      'note'      => $sanitizedData['note'],
      'is_active' => $sanitizedData['is_active'],
    ]);

    if (!$shortUrl) {
      return response()->json([
        'success' => false,
        'message' => 'Failed to generate unique short code after multiple attempts'
      ], 500);
    }

    return response()->json([
      'success' => true,
      'message' => 'Short URL created successfully',
      'data'    => new ShortUrlResource($shortUrl)
    ], 201);
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
