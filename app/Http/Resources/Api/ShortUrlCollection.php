<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ShortUrlCollection extends ResourceCollection
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
        'total_records'   => $this->total(),
        'items_on_page'   => $this->count(),
        'per_page'        => $this->perPage(),
        'current_page'    => $this->currentPage(),
        'total_pages'     => $this->lastPage(),
        'has_more_pages'  => $this->hasMorePages(),
      ],
    ];
  }
}