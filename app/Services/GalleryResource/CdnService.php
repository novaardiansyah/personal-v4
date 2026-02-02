<?php

/*
 * Project Name: personal-v4
 * File: CdnService.php
 * Created Date: Friday January 16th 2026
 *
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 *
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

declare(strict_types=1);

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
    /** @var \Illuminate\Http\Client\Response $response */
    $response = Http::withToken($this->apiKey)->delete("{$this->baseUrl}/{$id}");
    return $response;
  }

  public function deleteByGroupCode(string $groupCode, ?string $size = null): Response
  {
    /** @var \Illuminate\Http\Client\Response $response */
    $response = Http::withToken($this->apiKey)->delete("{$this->baseUrl}/{$groupCode}", [
      'size' => $size
    ]);
    return $response;
  }

  public function forceDelete(string|int $id): Response
  {
    /** @var \Illuminate\Http\Client\Response $response */
    $response = Http::withToken($this->apiKey)->delete("{$this->baseUrl}/{$id}/force");
    return $response;
  }

  public function restore(string|int $id): Response
  {
    /** @var \Illuminate\Http\Client\Response $response */
    $response = Http::withToken($this->apiKey)->post("{$this->baseUrl}/{$id}/restore");
    return $response;
  }

  public function upload(string $filePath, ?string $description = null, bool $isPrivate = false, ?string $subjectType = null, ?int $subjectId = null, ?string $dir = 'gallery'): Response|null
  {
    $disk = Storage::disk('public');
    $fileContent = $disk->get($filePath);

    if ($fileContent === null) return null;

    $mimeType = $disk->mimeType($filePath);
    $fileName = basename($filePath);

    $postData = [
      'description' => $description ?? '',
      'is_private' => $isPrivate ? 'true' : 'false',
    ];

    if ($subjectType && $subjectId) {
      $postData['subject_type'] = $subjectType;
      $postData['subject_id'] = $subjectId;
    }

    $postData['dir'] = $dir;

    /** @var \Illuminate\Http\Client\Response $response */
    $response = Http::withToken($this->apiKey)
      ->attach('file', $fileContent, $fileName, ['Content-Type' => $mimeType])
      ->post("{$this->baseUrl}/upload", $postData);

    return $response;
  }
}
