<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use HeroQR\Core\QRCodeGenerator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[ObservedBy(\App\Observers\ShortUrlObserver::class)]
class ShortUrl extends Model
{
  use SoftDeletes;
  protected $table = 'short_urls';
  protected $fillable = ['file_download_id', 'user_id', 'code', 'qrcode', 'note', 'note', 'long_url', 'short_code', 'tiny_url', 'is_active', 'clicks'];
  protected $casts = [
    'is_active' => 'boolean',
    'clicks' => 'integer',
  ];


  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  public function getShortCodeAttribute($value)
  {
    $domain = getSetting('short_url_domain');
    return $domain ? $domain . '/' . $value : $value;
  }

  public function getCleanShortCode(): string
  {
    $value = $this->short_code;
    $domain = getSetting('short_url_domain');
    return str_replace($domain . '/', '', $value);
  }

  /**
   * Generate a unique short code with retry logic
   */
  public static function generateUniqueShortCode(int $maxLength = 7, int $maxAttempts = 3): ?string
  {
    $attempts = 0;

    while ($attempts < $maxAttempts) {
      $str = Str::random($maxLength);
      $exist = self::where('short_code', $str)->first();

      if (!$exist) {
        return $str;
      }

      $attempts++;
    }

    return null;
  }

  /**
   * Generate and save QR code for this short URL
   */
  public function generateQRCode(): bool
  {
    try {
      $qrCodeManager = new QRCodeGenerator();
      $short_code = $this->short_code;

      $qrCode = $qrCodeManager
        ->setData($short_code)
        ->generate();

      Storage::disk('public')->makeDirectory('qrcodes/short-urls');

      if ($this->qrcode && Storage::disk('public')->exists($this->qrcode)) {
        Storage::disk('public')->delete($this->qrcode);
      }

      $path = 'qrcodes/short-urls/' . $this->getCleanShortCode();
      $qrCode->saveTo(Storage::disk('public')->path($path));

      $this->update([
        'qrcode' => $path . '.png'
      ]);

      return true;
    } catch (\Exception $e) {
      return false;
    }
  }

  /**
   * Create a new short URL with automatic code generation
   */
  public static function createShortUrl(array $data): ?self
  {
    $uniqueCode = self::generateUniqueShortCode();

    if (!$uniqueCode) {
      return null;
    }

    $shortUrl = self::create([
      'user_id'    => getUser()->id ?? null,
      'code'       => getCode('short_url'),
      'short_code' => $uniqueCode,
      'str_code'   => $uniqueCode,
      'long_url'   => $data['long_url'],
      'note'       => $data['note'] ?? null,
      'is_active'  => $data['is_active'] ?? true,
    ]);

    // Generate QR code after creation
    if ($shortUrl) {
      $shortUrl->generateQRCode();
    }

    return $shortUrl;
  }

  public function FileDownload(): BelongsTo
  {
    return $this->belongsTo(FileDownload::class, 'file_download_id', 'id');
  }
}
