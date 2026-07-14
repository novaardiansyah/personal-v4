<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CalendarCategoryResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    return [
      'id'         => $this->id,
      'code'       => $this->code,
      'name'       => $this->name,
      'color'      => $this->color,
      'is_default' => $this->is_default,
      'events_count' => $this->whenCounted('events'),
      'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
      'deleted_at' => $this->deleted_at?->format('Y-m-d H:i:s'),
    ];
  }
}