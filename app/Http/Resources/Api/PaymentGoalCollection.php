<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PaymentGoalCollection extends ResourceCollection
{
  /**
   * Transform the resource collection into an array.
   *
   * @return array<int|string, mixed>
   */
  public function toArray(Request $request): array
  {
    return [
      'data' => $this->collection,
      'meta' => [
        'total' => $this->total(),
        'count' => $this->count(),
        'per_page' => $this->perPage(),
        'current_page' => $this->currentPage(),
        'total_pages' => $this->lastPage(),
        'from' => $this->firstItem(),
        'to' => $this->lastItem(),
        'has_more_pages' => $this->hasMorePages(),
      ],
      'links' => [
        'first' => $this->url(1),
        'last' => $this->url($this->lastPage()),
        'prev' => $this->previousPageUrl(),
        'next' => $this->nextPageUrl(),
      ],
    ];
  }
}