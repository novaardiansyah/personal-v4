<?php

namespace App\Services\GalleryResource;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class CdnService
{
  protected string $baseUrl;
  protected string $apiKey;

  public function __construct()
  {
    $this->baseUrl = config('services.self.cdn_api_url') . '/galleries';
    $this->apiKey = config('services.self.cdn_api_key');
  }

  public function delete(string|int $id): Response
  {
    return Http::withToken($this->apiKey)->delete("{$this->baseUrl}/{$id}");
  }

  public function forceDelete(string|int $id): Response
  {
    return Http::withToken($this->apiKey)->delete("{$this->baseUrl}/{$id}/force");
  }

  public function restore(string|int $id): Response
  {
    return Http::withToken($this->apiKey)->post("{$this->baseUrl}/{$id}/restore");
  }

  public function upload(string $filePath, ?string $description = null, bool $isPrivate = false): Response
  {
    $disk = Storage::disk('public');
    $fileContent = $disk->get($filePath);
    $mimeType = $disk->mimeType($filePath);
    $fileName = basename($filePath);

    return Http::withToken($this->apiKey)
      ->attach('file', $fileContent, $fileName, ['Content-Type' => $mimeType])
      ->post("{$this->baseUrl}/upload", [
        'description' => $description ?? '',
        'is_private' => $isPrivate ? 'true' : 'false',
      ]);
  }
}
