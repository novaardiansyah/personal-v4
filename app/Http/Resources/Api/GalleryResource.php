<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class GalleryResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    return [
      'id'          => $this->id,
      'file_name'   => $this->file_name,
      'url'         => Storage::disk('public')->url($this->file_path),
      'file_size'   => $this->file_size,
      'is_private'  => $this->is_private,
      'description' => $this->description,
      'created_at'  => $this->created_at,
      'updated_at'  => $this->updated_at,
      'deleted_at'  => $this->deleted_at,
    ];
  }
}
