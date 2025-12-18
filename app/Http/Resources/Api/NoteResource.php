<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoteResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'code' => $this->code,
      'title' => $this->title,
      'content' => $this->content,
      'is_pinned' => $this->is_pinned,
      'is_archived' => $this->is_archived,

      'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
      'deleted_at' => $this->deleted_at?->format('Y-m-d H:i:s'),
    ];
  }
}
