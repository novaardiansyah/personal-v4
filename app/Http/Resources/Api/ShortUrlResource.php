<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShortUrlResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'         => $this->id,
      'uid'        => $this->code,
      'short_url'  => $this->short_code,
      'long_url'   => $this->long_url,
      'note'       => $this->note,
      'is_active'  => $this->is_active,
      'clicks'     => $this->clicks ?? 0,
      'qrcode_url' => $this->qrcode ? asset('storage/' . $this->qrcode) : null,
      'updated_at' => $this->updated_at,
    ];
  }
}