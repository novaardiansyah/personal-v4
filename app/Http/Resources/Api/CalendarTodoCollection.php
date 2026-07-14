<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CalendarTodoCollection extends ResourceCollection
{
  /**
   * Transform the resource collection into an array.
   *
   * @return array<int|string, mixed>
   */
  public function toArray($request): array
  {
    return [
      'data' => $this->collection,
    ];
  }
}