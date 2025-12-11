<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogTagResource extends JsonResource
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
      'name' => $this->name,
      'slug' => $this->slug,
      'description' => $this->description,
      'color_hex' => $this->color_hex,
      'display_order' => $this->display_order,
      'usage_count' => $this->usage_count,
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
    ];
  }
}
